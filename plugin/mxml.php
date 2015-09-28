<?php
/**
 * xml格式內容转换为数组
 * @author lihui
 * @version $Id: mxml.php 1614 2015-05-16 09:58:45Z lihui$
 */
class Plugin_Mxml
{
    var $vals;
    
    function Plugin_Mxml($data)
    {
        $xml_parser = xml_parser_create_ns();
        xml_parse_into_struct($xml_parser, $data, $this->vals);
        xml_parser_free($xml_parser);
    }
    
    function parse()
    {
        foreach($this->vals as $row)
        {
            if ($row['type'] == 'complete')
            {
                $xml_parse_str = '$xml_parse_arr';
                for($i = 1; $i < $row['level']; $i++)
                {
                    eval("\$level_num = \$level_num_$i - 1;");
                    $xml_parse_str .= "['{$level[$i]['tag']}']['{$level_num}']";
                    eval("{$xml_parse_str}['pram'] = \$level[\$i]['attributes'];");
                }
                eval("{$xml_parse_str}['{$row['tag']}']['value'] = '{$row['value']}';");
                eval("{$xml_parse_str}['{$row['tag']}']['pram'] = '{$row['attributes']}';");
             }
             elseif($row['type'] == 'open')
             {
                eval("\$level_num_{$row['level']}++;");
                $level[$row['level']]['tag'] = $row['tag'];
                $level[$row['level']]['attributes'] = $row['attributes'];
            }
        }
        return $xml_parse_arr;
    }
    
    function simple_parse()
    {
        foreach ($this->vals as $row)
        {
            if($row['type'] == 'complete')
            {
                $xml_parse_str = '$xml_parse_arr';
                for($i = 1; $i < $row['level']; $i++)
                {
                    $xml_parse_str .= "['{$level[$i]['tag']}']";
                    eval("{$xml_parse_str}['pram'] = \$level[\$i]['attributes'];");
                }
                eval("{$xml_parse_str}['{$row['tag']}']['value'] = '{$row['value']}';");
                eval("{$xml_parse_str}['{$row['tag']}']['pram'] = '{$row['attributes']}';");
            }elseif($row['type'] == 'open')
            {
                $level[$row['level']]['tag'] = $row['tag'];
                $level[$row['level']]['attributes'] = $row['attributes'];
            }
        }
        return $xml_parse_arr;
    }
}