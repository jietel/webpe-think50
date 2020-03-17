<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\template\taglib;

use think\template\TagLib;

/**
 * CX标签库解析类(@jayter精简)
 * @category   Think
 * @package  Think
 * @subpackage  Driver.Taglib
 * @author    liu21st <liu21st@gmail.com>
 * Modify: 	jayter <jayter2@qq.com>
 * 去掉无用
 */
class Cx extends Taglib
{

    // 标签定义
    protected $tags = [
            // 标签定义： attr 属性列表 close是否闭合<xx></xx>（0无 1有 默认1） alias 标签别名 level 嵌套层次
        	'php'        => ['attr' => ''],
    		'volist'     => ['attr' => 'name,id,offset,length,key,mod,empty','level'=>3],//, 'alias' => 'iterate'
    		'foreach'    => ['attr' => 'name,id,item,key,offset,length,mod,empty','level'=>3,'expression' => true],
        	'if'         => ['attr' => 'condition', 'expression' => true],
        	'elseif'     => ['attr' => 'condition', 'close' => 0, 'expression' => true],
        	'else'       => ['attr' => '', 'close' => 0],
        	'switch'     => ['attr' => 'name', 'expression' => true],
        	'case'       => ['attr' => 'value,break', 'expression' => true],
        	'default'    => ['attr' => '', 'close' => 0],
        	'compare'    => ['attr' => 'name,value,type', 'alias' => ['eq,neq,gt,lt,egt,elt,heq,nheq', 'type']],
        	'range'      => ['attr' => 'name,value,type', 'alias' => ['in,notin,between,notbetween', 'type']],
        	'empty'      => ['attr' => 'name'],
        	'notempty'   => ['attr' => 'name'],
        	'assign'     => ['attr' => 'name,value', 'close' => 0],
        	'for'        => ['attr' => 'start,end,name,comparison,step'],
     
    ];

    /**
     * php标签解析
     * 格式：
     * {php}echo $name{/php}
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagPhp($tag, $content)
    {
        $parseStr = '<?php ' . $content . ' ?>';
        return $parseStr;
    }

    /**
     * volist标签解析 循环输出数据集
     * 格式：
     * {volist name="userList" id="user" empty=""}
     * {user.username}
     * {user.email}
     * {/volist}
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string|void
     */
    public function tagVolist($tag, $content)
    {
        $name   = $tag['name'];
        $id     = $tag['id'];
        $key    = !empty($tag['key']) ? $tag['key'] : 'i';
        $mod    = isset($tag['mod']) ? $tag['mod'] : '2';
        $empty  = isset($tag['empty']) ? preg_replace('/\[([^\]]+)\]/i', '<$1>', $tag['empty']) : '';
        $offset = !empty($tag['offset']) && is_numeric($tag['offset']) ? intval($tag['offset']) : 0;
        $length = !empty($tag['length']) && is_numeric($tag['length']) ? intval($tag['length']) : 'null';
        $parseStr = '<?php ';
        $name = $this->autoBuildVar($name);

        // 设置了输出数组长度
        if (0 != $offset || 'null' != $length) {
            $parseStr .= '$__LIST__ = is_array(' . $name . ') ? array_slice(' . $name . ',' . $offset . ',' . $length . ', true) : ' . $name . '->slice(' . $offset . ',' . $length . ', true); ';
        } else {
            $parseStr .= ' $__LIST__ = ' . $name . ';';
        }
        $parseStr .= 'if( count($__LIST__)>0 ) : ';
        $parseStr .= '$'.$key.'=0;foreach($__LIST__ as $key=>$' . $id . '): ';
        $parseStr .= '$mod = ($' . $key . ' % ' . $mod . ' );';
        $parseStr .= '++$' . $key . ';?>';
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; else: echo "' . $empty . '";endif; ?>';

        if (!empty($parseStr)) {
            return $parseStr;
        }
        return;
    }

    /**
     * foreach标签解析 循环输出数据集
     * 格式：
     * {foreach name="userList" id="user" key="key" index="i" mod="2" offset="3" length="5" empty=""}
     * {user.username}
     * {/foreach}
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string|void
     */
    public function tagForeach($tag, $content)
    {
        // 直接使用表达式
        if (!empty($tag['expression'])) {
            $expression = $this->autoBuildVar($expression);
            $parseStr   = '<?php foreach(' . $expression . '): ?>';
            $parseStr .= $content;
            $parseStr .= '<?php endforeach; ?>';
            return $parseStr;
        }
        $name   = $tag['name'];
        $key    = !empty($tag['key']) ? $tag['key'] : 'key';
        $item   = !empty($tag['id']) ? $tag['id'] : $tag['item'];
        $empty  = isset($tag['empty']) ? preg_replace('/\[([^\]]+)\]/i', '<$1>', $tag['empty']) : '';
        $offset = !empty($tag['offset']) && is_numeric($tag['offset']) ? intval($tag['offset']) : 0;
        $length = !empty($tag['length']) && is_numeric($tag['length']) ? intval($tag['length']) : 'null';

        $parseStr = '<?php ';
        $name = $this->autoBuildVar($name);

        // 设置了输出数组长度
        if (0 != $offset || 'null' != $length) {
        	$var = !isset($var) ? '$_' . uniqid() : $var;
            $parseStr .= $var . ' = is_array(' . $name . ') ? array_slice(' . $name . ',' . $offset . ',' . $length . ', true) : ' . $name . '->slice(' . $offset . ',' . $length . ', true); ';
        } else {
            $var = &$name;
        }
        $parseStr .= 'if( count(' . $var . ')>0 ) : ';
        // 设置索引项
        if (isset($tag['index'])) {
            $index = $tag['index'];
            $parseStr .= '$' . $index . '=0; ';
        }
        $parseStr .= 'foreach(' . $var . ' as $' . $key . '=>$' . $item . '): ';
        // 设置索引项
        if (isset($tag['index'])) {
            $index = $tag['index'];
            if (isset($tag['mod'])) {
                $mod = (int) $tag['mod'];
                $parseStr .= '$mod = ($' . $index . ' % ' . $mod . '); ';
            }
            $parseStr .= '++$' . $index . '; ';
        }
        $parseStr .= '?>';
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; else: echo "' . $empty . '";endif;  ?>';
        if (!empty($parseStr)) {
            return $parseStr;
        }
        return;
    }

    /**
     * if标签解析
     * 格式：
     * {if condition=" $a eq 1"}
     * {elseif condition="$a eq 2" /}
     * {else /}
     * {/if}
     * 表达式支持 eq neq gt egt lt elt == > >= < <= or and || &&
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagIf($tag, $content)
    {
        $condition = !empty($tag['expression']) ? $tag['expression'] : $tag['condition'];
        $condition = $this->parseCondition($condition);
        $parseStr  = '<?php if(' . $condition . '): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * elseif标签解析
     * 格式：见if标签
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagElseif($tag, $content)
    {
        $condition = !empty($tag['expression']) ? $tag['expression'] : $tag['condition'];
        $condition = $this->parseCondition($condition);
        $parseStr  = '<?php elseif(' . $condition . '): ?>';
        return $parseStr;
    }

    /**
     * else标签解析
     * 格式：见if标签
     * @access public
     * @param array $tag 标签属性
     * @return string
     */
    public function tagElse($tag)
    {
        $parseStr = '<?php else: ?>';
        return $parseStr;
    }

    /**
     * switch标签解析
     * 格式：
     * {switch name="a.name"}
     * {case value="1" break="false"}1{/case}
     * {case value="2" }2{/case}
     * {default /}other
     * {/switch}
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagSwitch($tag, $content)
    {
        $name     = !empty($tag['expression']) ? $tag['expression'] : $tag['name'];
        $name     = $this->autoBuildVar($name);
        $parseStr = '<?php switch(' . $name . '): ?>' . $content . '<?php endswitch; ?>';
        return $parseStr;
    }

    /**
     * case标签解析 需要配合switch才有效
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagCase($tag, $content)
    {
        $value = isset($tag['expression']) ? $tag['expression'] : $tag['value'];
        $flag  = substr($value, 0, 1);
        if ('$' == $flag || ':' == $flag) {
            $value = $this->autoBuildVar($value);
            $value = 'case ' . $value . ':';
        } elseif (strpos($value, '|')) {
            $values = explode('|', $value);
            $value  = '';
            foreach ($values as $val) {
                $value .= 'case "' . addslashes($val) . '":';
            }
        } else {
            $value = 'case "' . $value . '":';
        }
        $parseStr = '<?php ' . $value . ' ?>' . $content;
        $isBreak  = isset($tag['break']) ? $tag['break'] : '';
        if ('' == $isBreak || $isBreak) {
            $parseStr .= '<?php break; ?>';
        }
        return $parseStr;
    }

    /**
     * default标签解析 需要配合switch才有效
     * 使用： {default /}ddfdf
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagDefault($tag)
    {
        $parseStr = '<?php default: ?>';
        return $parseStr;
    }

    /**
     * compare标签解析
     * 用于值的比较 支持 eq neq gt lt egt elt heq nheq 默认是eq
     * 格式： {compare name="" type="eq" value="" }content{/compare}
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagCompare($tag, $content)
    {
        $name  = $tag['name'];
        $value = $tag['value'];
        $type  = isset($tag['type']) ? $tag['type'] : 'eq'; // 比较类型
        $name  = $this->autoBuildVar($name);
        $flag  = substr($value, 0, 1);
        if ('$' == $flag || ':' == $flag) {
            $value = $this->autoBuildVar($value);
        } else {
            $value = '\'' . $value . '\'';
        }
        $type     = $this->parseCondition(' ' . $type . ' ');
        $parseStr = '<?php if(' . $name . ' ' . $type . ' ' . $value . '): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * range标签解析
     * 如果某个变量存在于某个范围 则输出内容 type= in 表示在范围内 否则表示在范围外
     * 格式： {range name="var|function"  value="val" type='in|notin' }content{/range}
     * example: {range name="a"  value="1,2,3" type='in' }content{/range}
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagRange($tag, $content)
    {
        $name  = $tag['name'];
        $value = $tag['value'];
        $type  = isset($tag['type']) ? $tag['type'] : 'in'; // 比较类型

        $name = $this->autoBuildVar($name);
        $flag = substr($value, 0, 1);
        if ('$' == $flag || ':' == $flag) {
            $value = $this->autoBuildVar($value);
            $str   = 'is_array(' . $value . ')?' . $value . ':explode(\',\',' . $value . ')';
        } else {
            $value = '"' . $value . '"';
            $str   = 'explode(\',\',' . $value . ')';
        }
        if ('between' == $type) {
            $parseStr = '<?php $_RANGE_VAR_=' . $str . ';if(' . $name . '>= $_RANGE_VAR_[0] && ' . $name . '<= $_RANGE_VAR_[1]):?>' . $content . '<?php endif; ?>';
        } elseif ('notbetween' == $type) {
            $parseStr = '<?php $_RANGE_VAR_=' . $str . ';if(' . $name . '<$_RANGE_VAR_[0] || ' . $name . '>$_RANGE_VAR_[1]):?>' . $content . '<?php endif; ?>';
        } else {
            $fun      = ('in' == $type) ? 'in_array' : '!in_array';
            $parseStr = '<?php if(' . $fun . '((' . $name . '), ' . $str . ')): ?>' . $content . '<?php endif; ?>';
        }
        return $parseStr;
    }


    /**
     * empty标签解析
     * 如果某个变量为empty 则输出内容
     * 格式： {empty name="" }content{/empty}
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagEmpty($tag, $content)
    {
        $name     = $tag['name'];
        $name     = $this->autoBuildVar($name);
        $parseStr = '<?php if(empty(' . $name . ') || ((' . $name . ' instanceof \think\Collection || ' . $name . ' instanceof \think\Paginator ) && ' . $name . '->isEmpty())): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * notempty标签解析
     * 如果某个变量不为empty 则输出内容
     * 格式： {notempty name="" }content{/notempty}
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagNotempty($tag, $content)
    {
        $name     = $tag['name'];
        $name     = $this->autoBuildVar($name);
        $parseStr = '<?php if(!(empty(' . $name . ') || ((' . $name . ' instanceof \think\Collection || ' . $name . ' instanceof \think\Paginator ) && ' . $name . '->isEmpty()))): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * assign标签解析
     * 在模板中给某个变量赋值 支持变量赋值
     * 格式： {assign name="" value="" /}
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagAssign($tag, $content)
    {
        $name = $this->autoBuildVar($tag['name']);
        $flag = substr($tag['value'], 0, 1);
        if ('$' == $flag || ':' == $flag) {
            $value = $this->autoBuildVar($tag['value']);
        } else {
            $value = '\'' . $tag['value'] . '\'';
        }
        $parseStr = '<?php ' . $name . ' = ' . $value . '; ?>';
        return $parseStr;
    }

    /**
     * for标签解析
     * 格式：
     * {for start="" end="" comparison="" step="" name=""}
     * content
     * {/for}
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagFor($tag, $content)
    {
        //设置默认值
        $start      = 0;
        $end        = 0;
        $step       = 1;
        $comparison = 'lt';
        $name       = 'i';
        $rand       = mt_rand(10000,99999); //添加随机数，防止嵌套变量冲突
        //获取属性
        foreach ($tag as $key => $value) {
            $value = trim($value);
            $flag  = substr($value, 0, 1);
            if ('$' == $flag || ':' == $flag) {
                $value = $this->autoBuildVar($value);
            }

            switch ($key) {
                case 'start':
                    $start = $value;
                    break;
                case 'end':
                    $end = $value;
                    break;
                case 'step':
                    $step = $value;
                    break;
                case 'comparison':
                    $comparison = $value;
                    break;
                case 'name':
                    $name = $value;
                    break;
            }
        }

        $parseStr = '<?php $__FOR_START_' . $rand . '=' . $start . ';$__FOR_END_' . $rand . '=' . $end . ';';
        $parseStr .= 'for($' . $name . '=$__FOR_START_' . $rand . ';' . $this->parseCondition('$' . $name . ' ' . $comparison . ' $__FOR_END_' . $rand) . ';$' . $name . '+=' . $step . '){ ?>';
        $parseStr .= $content;
        $parseStr .= '<?php } ?>';
        return $parseStr;
    }


}
