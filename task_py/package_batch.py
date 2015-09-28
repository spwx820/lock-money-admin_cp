#coding=utf8

import os,sys
from datetime import datetime
import time
import urllib2,json
import logging
from logging.handlers import RotatingFileHandler
import socket
from pprint import pformat
from collections import OrderedDict
from multiprocessing import Pool
import redis
import ConfigParser

reload(sys)
sys.setdefaultencoding('utf8')

cur_dir = os.path.dirname(os.path.abspath(__file__))

rlog = None

CONST_PAK_URL = 'http://{domain}/admin_invite_pk.do?ispak=1&uid={uid}&channel={channel}'
key = 'ZHUAN_ADMIN_TASK_PACKAGE_BATCH' # job2do list
rtn_key = key+'_RESULT' # result list
check_key = '_TMP_LOCK_' + key # 检查是否有任务在执行

def initLog():
    global rlog
    rlog = logging.getLogger()
    rlog.setLevel(logging.DEBUG)
    rlog.handlers = [ _h for _h in rlog.handlers if not isinstance(_h, logging.StreamHandler) ]
    log_sh=logging.StreamHandler()
    log_sh.setLevel(logging.INFO)
    log_sh.setFormatter(logging.Formatter('%(asctime)s %(levelname)s %(processName)s %(funcName)s %(lineno)d | %(message)s', '%H:%M:%S'))
    rlog.addHandler(log_sh) # add handler(s) to root logger
    
    log_dir = os.path.join(cur_dir, 'log') # dir of script log file
    if not os.path.exists(log_dir):
        os.makedirs(log_dir)
    logfile=os.path.join(log_dir, 'package.log')
    log_file = RotatingFileHandler(logfile, maxBytes=10*1024*1024, backupCount=2, encoding='utf8')
    log_file.setLevel(logging.DEBUG)
    log_file.setFormatter(logging.Formatter('%(asctime)s %(levelname)s %(name)s %(processName)s %(funcName)s %(lineno)d | %(message)s', '%H:%M:%S'))
    rlog.addHandler(log_file) # add handler(s) to root logger


class myRedirectHandler(urllib2.HTTPRedirectHandler):
    def http_error_302(self, req, fp, code, msg, headers):
        pass


def doJob(in_data):
    info = rlog.info
    url, rtn = None, False

    opener = urllib2.build_opener(myRedirectHandler)
    opener.addheaders=[('User-Agent', 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:24.0) Gecko/20100101 Firefox/24.0')]
    req = urllib2.Request(CONST_PAK_URL.format(**in_data))
#-#    info('in_data: %s', pformat(in_data))

    a = time.time()
    try:
        r = opener.open(req, timeout=5)
        if r:
            res, rurl, code=r.read(), r.geturl(), r.getcode()

    except urllib2.HTTPError as e:
        code = e.code
        if code == 302 :
            url = e.hdrs.get('Location', None)
            if url:
                rtn = True
        else: info('HTTPError！ %s',e)
#-#    except urllib2.URLError as e:
#-#        info('URLError! %s, %s | %s',repr(e),e,url)
    except socket.timeout as e:
        info('receive data timeout ! %s',url)
#-#    except socket.error as e:
#-#        info('socket.error! %s',e)
#-#    except IOError as e:
#-#        info('IOError! %s | %s',e,url)
    except StandardError as e:
        debug('StandardError! %s | %s',e,url)
#-#    except httplib.InvalidURL as e:
#-#        info('InvalidURL! %s | %s',e,url)
#-#    except httplib.BadStatusLine as e:
#-#        info('BadStatusLine! %s | %s',e,url)

    return {'_k': in_data['_k'], 'uid': in_data['uid'], 'channel': in_data['channel'], 'url': url, 'rtn': rtn, 'ctime': datetime.now().strftime('%Y-%m-%d %H:%M:%S %f'), 'elapse': time.time()-a }


def readCfg(f_config, dft_cfg):
    info, debug = rlog.info, rlog.debug
    _cfg = dict(dft_cfg)
    if os.path.exists(f_config):
        _cfg.update( dict( (_k.strip(), _v.strip("\"' ")) for _k, _v in (x.strip().split('=') for x in open(f_config).read().split('\n') if x.strip() and not x.strip().startswith('#') ) ) )
    else:
        info('file not exists !', f_config)
    cfg = lambda _: _
    for _k, _v in _cfg.items():
        if _k in dft_cfg and type(dft_cfg[_k]) != type(_v):
                _v = type(dft_cfg[_k])(_v)
        setattr(cfg, _k, _v)
    info('cfg:\n%s', pformat(cfg.__dict__))
    return cfg


def doWork():
    initLog()
    info, debug = rlog.info, rlog.debug

    f_config = os.path.join(cur_dir, 'env.conf')
    cfg = readCfg(f_config, {'redis_host':'192.168.199.8','redis_port': 6379, 'redis_db': 6, 'pool_size': 8, 'domain': 'fake-b-api.aa123bb.com' })

    r = redis.Redis(host=cfg.redis_host, port=cfg.redis_port, db=cfg.redis_db)

    # double check
    if r.incr(check_key) != 1:
        info('lock exists! do nothing and exit. %s', check_key)
        return

    try:
        nr_task = 0
        if r.exists(key):
            nr_task = r.llen(key) # 列表中的任务数量，可能有重复的
#-#            r.delete(rtn_key) # clear result list
            p = Pool(processes = cfg.pool_size)

            d_job = OrderedDict( (x, json.loads(x)) for x in r.lrange(key, 0, nr_task) )
            l_job = []
            for _k, _v in d_job.items(): # 追加参数
                _v['_k'] = _k
                _v['domain'] = cfg.domain
                l_job.append( _v )
            del d_job

            nr_task = len(l_job) # 排重后的任务数量
            info('total %d uniq task(s)', nr_task)
            it = p.imap_unordered(doJob,l_job)
            nr_done = 0
            for _rslt in it:
                nr_done += 1
                # test output
                _uid, _channel, _url, _rtn = _rslt['uid'], _rslt['channel'], _rslt['url'], _rslt['rtn']
                debug('%3d/%-3d task (%s, %s) %s %s', nr_done, nr_task, _uid, _channel, _rtn, _url)
                r.lrem(key, _rslt['_k'], 0) # delete from job list
                _rslt.pop('_k', None)
                r.lpush(rtn_key, json.dumps(_rslt)) # add to result list
        else:
            info('key %s not exists, do nothing', key)

        if nr_task>0 and r.exists(rtn_key):
            info('total %d item(s) in key %s', r.llen(rtn_key), rtn_key)
            # test output
            l_rslt = r.lrange(rtn_key, 0, r.llen(rtn_key))
            debug('l_rslt:\n%s', pformat(l_rslt))

        # test output
        if r.exists(key):
            info('still %d item(s) in key %s', r.llen(key), key)

        info('done.')
    finally:
        r.delete(check_key)

if __name__ == '__main__':
    f_pid = os.path.join( os.path.dirname(os.path.abspath(__file__)), 'pid_pkg_batch.txt')
    if os.path.exists(f_pid):
        print '''
        **************************************************************
            app exit !!!
            reason: %s already exists ! please check:
            1) this app is already running ?
            2) last run didn\'t exit normally ?
            if this app is REALLY not running, delete pid.txt and try launch this app again.'''%f_pid
        sys.exit(-2)
    open(f_pid, 'w').write('%d'%os.getpid())
    try:
        doWork()
    finally:
        os.remove(f_pid)




