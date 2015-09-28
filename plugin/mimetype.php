<?php
/**
 * http mimetype 类
 *
 * @author liuxp
 * @author liuguagnzhao
 * $Id: mimetype.php 1 2011-04-08 07:42:35Z xiaoping1 $
 */
class plugin_mimetype
{
	static private $mimetypes = Array(
         "ez" => "application/andrew-inset",
         "hqx" => "application/mac-binhex40",
         "cpt" => "application/mac-compactpro",
         "doc" => "application/msword",
         "xls" => "application/vnd.ms-excel",
         "chm" => "application/mshelp",
         "bin" => "application/octet-stream",
         "dms" => "application/octet-stream",
         "lha" => "application/octet-stream",
         "lzh" => "application/octet-stream",
         "exe" => "application/octet-stream",
         "class" => "application/octet-stream",
         "so" => "application/octet-stream",
         "dll" => "application/octet-stream",
         "oda" => "application/oda",
         "pdf" => "application/pdf",
         "ai" => "application/postscript",
         "eps" => "application/postscript",
         "ps" => "application/postscript",
         "smi" => "application/smil",
         "smil" => "application/smil",
         "wbxml" => "application/vnd.wap.wbxml",
         "wmlc" => "application/vnd.wap.wmlc",
         "wmlsc" => "application/vnd.wap.wmlscriptc",
         "bcpio" => "application/x-bcpio",
         "vcd" => "application/x-cdlink",
         "pgn" => "application/x-chess-pgn",
         "cpio" => "application/x-cpio",
         "csh" => "application/x-csh",
         "dcr" => "application/x-director",
         "dir" => "application/x-director",
         "dxr" => "application/x-director",
         "dvi" => "application/x-dvi",
         "spl" => "application/x-futuresplash",
         "gtar" => "application/x-gtar",
         "hdf" => "application/x-hdf",
         "js" => "application/x-javascript",
         "skp" => "application/x-koan",
         "skd" => "application/x-koan",
         "skt" => "application/x-koan",
         "skm" => "application/x-koan",
         "latex" => "application/x-latex",
         "nc" => "application/x-netcdf",
         "cdf" => "application/x-netcdf",
         "sh" => "application/x-sh",
         "php" => "application/x-php",
         "shar" => "application/x-shar",
         "swf" => "application/x-shockwave-flash",
         "sit" => "application/x-stuffit",
         "sv4cpio" => "application/x-sv4cpio",
         "sv4crc" => "application/x-sv4crc",
         "tar" => "application/x-tar",
         "tcl" => "application/x-tcl",
         "tex" => "application/x-tex",
         "texinfo" => "application/x-texinfo",
         "texi" => "application/x-texinfo",
         "t" => "application/x-troff",
         "tr" => "application/x-troff",
         "roff" => "application/x-troff",
         "man" => "application/x-troff-man",
         "me" => "application/x-troff-me",
         "ms" => "application/x-troff-ms",
         "ustar" => "application/x-ustar",
         "src" => "application/x-wais-source",
         "xhtml" => "application/xhtml+xml",
         "xht" => "application/xhtml+xml",
         "zip" => "application/zip",
         "rar" => "application/x-rar",
         "au" => "audio/basic",
         "snd" => "audio/basic",
         "mid" => "audio/midi",
         "midi" => "audio/midi",
         "kar" => "audio/midi",
         "mpga" => "audio/mpeg",
         "mp2" => "audio/mpeg",
         "mp3" => "audio/mpeg",
         "aif" => "audio/x-aiff",
         "aiff" => "audio/x-aiff",
         "aifc" => "audio/x-aiff",
         "m3u" => "audio/x-mpegurl",
         "ram" => "audio/x-pn-realaudio",
         "rm" => "audio/x-pn-realaudio",
         "rpm" => "audio/x-pn-realaudio-plugin",
         "ra" => "audio/x-realaudio",
         "wav" => "audio/x-wav",
         "pdb" => "chemical/x-pdb",
         "xyz" => "chemical/x-xyz",
         "bmp" => "image/bmp",
         "gif" => "image/gif",
         "ief" => "image/ief",
         "jpe" => "image/jpeg",
         "jpeg" => "image/jpeg",
         "jpg" => "image/jpeg",
         "png" => "image/png",
         "tiff" => "image/tiff",
         "tif" => "image/tif",
         "djvu" => "image/vnd.djvu",
         "djv" => "image/vnd.djvu",
         "wbmp" => "image/vnd.wap.wbmp",
         "ras" => "image/x-cmu-raster",
         "pnm" => "image/x-portable-anymap",
         "pbm" => "image/x-portable-bitmap",
         "pgm" => "image/x-portable-graymap",
         "ppm" => "image/x-portable-pixmap",
         "rgb" => "image/x-rgb",
         "xbm" => "image/x-xbitmap",
         "xpm" => "image/x-xpixmap",
         "xwd" => "image/x-windowdump",
         "igs" => "model/iges",
         "iges" => "model/iges",
         "msh" => "model/mesh",
         "mesh" => "model/mesh",
         "silo" => "model/mesh",
         "wrl" => "model/vrml",
         "vrml" => "model/vrml",
         "css" => "text/css",
         "html" => "text/html",
         "htm" => "text/html",
         "asc" => "text/plain",
         "txt" => "text/plain",
         "rtx" => "text/richtext",
         "rtf" => "text/rtf",
         "sgml" => "text/sgml",
         "sgm" => "text/sgml",
         "tsv" => "text/tab-seperated-values",
         "wml" => "text/vnd.wap.wml",
         "wmls" => "text/vnd.wap.wmlscript",
         "etx" => "text/x-setext",
         "xml" => "text/xml",
         "xsl" => "text/xml",
         "mpeg" => "video/mpeg",
         "mpg" => "video/mpeg",
         "mpe" => "video/mpeg",
         "qt" => "video/quicktime",
         "mov" => "video/quicktime",
         "mxu" => "video/vnd.mpegurl",
         "avi" => "video/x-msvideo",
         "movie" => "video/x-sgi-movie",
         "ice" => "x-conference-xcooltalk",
         "wmv"=>"video/x-ms-wmv",
         "wma"=>"audio/x-ms-wma",
         "asf"=>"video/x-msvideo"
      );

   //如果没有找到，默认返回的 MIME 类型
   static private $_defaultType = "application/octet-stream";

   /**
    * 获取文件对应的MIME类型
    *
    * @param string $filename   文件名
    * @return string            文件对应的MIME类型
    */
   static public function get_mime_type($filename)
   {
      //获取扩展名
      $filename = basename(strtolower($filename));
      $filename = explode('.', $filename);
      $filename = $filename[count($filename)-1];

      //返回 MIME 类型
      return self::find_type($filename);
   }

   /**
    * 获取mime类型
    */
   static public function get_mime_type2($file, $content = '')
   {
       if (PHP_VERSION >= 5.3) {
           if (function_exists('finfo_open')) {
               $mgc = null;
               // 手册
               if ((version_compare(PHP_VERSION, "5.3.11") >= 0 
                    && version_compare(PHP_VERSION, "5.4.0") < 0)
                   || version_compare(PHP_VERSION, "5.4.1") >= 0) {
                   $mgc = _APP_ . '/_config/magic.mgc';
                   if (!file_exists($mgc)) {
                       $mgc = null;
                   }
               }
               $finfo = null;
               if ($mgc) {
                   $finfo = @finfo_open(FILEINFO_MIME, $mgc);
               } else {
                   $finfo =  finfo_open(FILEINFO_MIME);
               }
               // $finfo = $mgc ? @finfo_open(FILEINFO_MIME, $mgc) : finfo_open(FILEINFO_MIME);
               if ($content != '') {
                   $file_mime = finfo_buffer($finfo, $content);
               } else {
                   $file_mime = finfo_file($finfo, $file);
               }
               finfo_close($finfo);
           } else {
               exit("PECL fileinfo not install  ");
           }
       } else {
           if ($content != '') {
               $tmpfname = tempnam(sys_get_temp_dir(), __FUNCTION__ . uniqid());
               file_put_contents($tmpfname, $content);
               $file_mime = mime_content_type($tmpfname);
               unlink($tmpfname);
           } else {
               $file_mime = mime_content_type($_FILES[$upload_name]['tmp_name']);
           }
       }
       return $file_mime;
   }


   /**
    * 通过扩展名，查找MIME 类型
    *
    * @param string $ext
    * @return string
    */
   static public function find_type($ext)
   {
      if (isset(self::$mimetypes[$ext])) {
         return self::$mimetypes[$ext];
      } else {
         return self::$_defaultType;
      }
   }

   /**
    * 更精准的MIME类型
    *
    * 通过文件内容，文件扩展名综合评定的结果
    */
   static public function get_exact_mime_type($file_path, $content)
   {
      //获取扩展名
      $filename = basename(strtolower($file_path));
      $filename = explode('.', $filename);
      $file_ext = $filename[count($filename)-1];
      
      // cmime_type: content_mime
      $content_mime_type = self::get_mime_type2($file_path, $content);
      $content_mime_prefix = substr($content_mime_type, 0, strpos($content_mime_type, '/'));
      $ext_mime_type = '';
      $ext_mime_parts = array();
      $exact_mime_type = $content_mime_type;

      if ($content_mime_prefix == 'image') {
          return $content_mime_type;
      } else if ($content_mime_prefix == 'text') {
          $content_mime_parts = explode(';', $content_mime_type);

          // 有些是以头缀先确定，因为可能get_mimetype获取的根本不准确。
          // 输出http头，以get_mimetype获取为优先，如果没有得到，再考虑使用mb_detect
          // 可能检测到的是这样的文件头
          // text/html; charset=unknown-8bit
          // text/x-c; charset=iso-8859-1 xx.js

          if ($file_ext == 'js' || $file_ext == 'css'
              || $file_ext == 'php') {
              //   || $file_ext == 'html' || $file_ext == 'htm') {
              $ext_mime_type = self::get_mime_type($file_ext);
              $content_mime_type = $ext_mime_type .= '; charset=unknown-8bit';
              $ext_mime_parts = explode(';', $ext_mime_type);
          } else {
              $ext_mime_parts = $content_mime_parts;
          }
          $exact_mime_type = $ext_mime_type;
          if (strstr(strtolower($content_mime_type), 'unknown') 
              || strstr(strtolower($ext_mime_type), 'unknown')) {
              $ary = array('GBK', 'GB2312', 'EUC-CN', 'CP936', 'BIG5', 'UTF-8');
              $char_encodeing = mb_detect_encoding($content, $ary, true);

              if (!$char_encodeing) {
                  // no way, no change
              } else {
                  if ($char_encodeing == 'CP936' ) {
                      $ext_mime_parts[1] = substr($ext_mime_parts[1], 0, strpos($ext_mime_parts[1], '=')+1) . 'gb2312';
                  } else {
                      $ext_mime_parts[1] = strtolower($char_encodeing);
                  }
                  $exact_mime_type = implode(';', $ext_mime_parts);
              }
          } else {
              // no change
              $exact_mime_type = implode(';', $ext_mime_parts);
          }
      }

      return $exact_mime_type;
   }

   /**
    * 获取MIME类型对应的扩展名
    *
    * @param string $mimetype   MIME类型
    * @return string            如果没有找到对应的扩展名，返回空
    */
   static public function getExtension( $mimetype )
   {
   		$exts = array_flip( self::$mimetypes );
        
        if ( isset($exts[$mimetype]))
        {
        	$ext = $exts[$mimetype];
        } else {
        	$ext = "";
        }

        return $ext;
   }
}
