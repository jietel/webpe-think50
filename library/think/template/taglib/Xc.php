<?php
// ----------------------------------------------------------------------
// XwebCms
// Copyright (c) 2016-2019 http://www.xwebcms.com All rights reserved.
// Author: jayter <jayter2@qq.com>
// ----------------------------------------------------------------------

namespace think\template\taglib;
use think\template\TagLib;
/**
 * XwebCms系统标签库(简称xc)
 * <xc:data table=""...>...</xc:data>
 */
class Xc extends TagLib{
    // 标签定义
    protected $tags   =  array(
        // 标签定义： attr 属性列表 close是否闭合<xx></xx>（0无 1有 默认1） alias 标签别名 level 嵌套层次
        'form'          =>  ['attr' => 'type,name,value,class','close'=>0],

        'cat'        	=>  ['attr' => 'id,ic,pid,data,result,mod,field,key,cache','level'=>1],
    	'type'      	=>  ['attr' => 'model,catid,pid,cache,result,key,mod,limit'],
    	'data'      	=>  ['attr' => 'table,field,join,group,where,order,cache,limit,result,key,mod'],
    	'links'   		=>  ['attr' => 'mod,key,type,limit,result,cache','level'=>1],
    	'poster'   		=>  ['attr' => 'mod,key,typeid,field,limit,result,cache,random','level'=>1],
    	'tags'   		=>  ['attr' => 'mod,key,limit,result,cache','level'=>1],
    );

    /**
     * 表单标签解析
     * <xc:form name="xxx" type="select" options="$xxx" value="$vvv" class="" xxx=""/>
     * type支持xweb\library\Xform类所有方法select|radio|checkbox|text|upload|editor|time等
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagForm($tag, $content) {
        $type       = isset($tag['type']) ? $tag['type'] : 'select';
        $name       = isset($tag['name']) ? $tag['name'] : '';
        $value      = isset($tag['value']) ? $tag['value'] : '';
        unset($tag['type'],$tag['name'],$tag['value']);
        $attrStr = "[";
        foreach ($tag as $key=>$val){
            $attrStr .= "'$key'=>".(substr($val,0,1)=='$' ? $val : "\"$val\"").',';
        }
        $attrStr.= "]";
        if(substr($value,0,1)!='$'){
        	$value = is_numeric($value) ? $value : "\"$value\"";
        }
        $name = $this->autoBuildVar($name);
        $parseStr = '<?php ';
        $parseStr.= 'echo \\xweb\\library\\Xform::'.$type.'('.$name.','.$value.','.$attrStr.')';
        $parseStr.= '; ?>';
        return $parseStr;
    }
































    // 获取栏目分类
    public function tagCat($tag,$content){
    	$cache 	= empty($tag['cache']) ? 0 : intval($tag['cache']);
    	$field  = empty($tag['field']) ? 'true' : '"catid,pid,'.$tag['field'].'"';
        $result = !empty($tag['result'])?$tag['result']:'cat';
        $key   	= !empty($tag['key'])?$tag['key']:'i';
        if(!empty($tag['ic']) || !empty($tag['id'])) {
            // 根据id/ic获取单个分类
            if(!empty($tag['ic'])){
            	$ic 	= $this->autoBuildVar($tag['ic']);
            	$where 	= "`ic`='$ic'";
            }else{
            	$id 	= is_numeric($tag['id']) ? $tag['id'] : $this->autoBuildVar($tag['id']);
            	$where 	= "`catid`='$id'";
            }
            $parseStr  	=  "<?php \$$result = M('Category')->field($field)->where($where)";
            if(!empty($cache)) $parseStr .= "->cache(true,$cache)";
            $parseStr 	.= "->find();";
            $parseStr 	.= "if(\$$result):?>$content";
        	$parseStr 	.= "<?php endif;?>";
        }else{
        	$pid	= is_numeric($tag['pid']) ? $tag['pid'] : $this->autoBuildVar($tag['pid']);
            $mod    = isset($tag['mod'])?$tag['mod']:'2';
        	$parseStr  = "<?php ";
        	$parseStr .= $pid!="$" ? " \$_CAT = array_parents(\$Categorys,$pid,'pid'); " : " \$_CAT = array_to_tree(\$Categorys,'catid','pid'); ";
            $parseStr .= "if(\$_CAT):\$$key=0;foreach(\$_CAT as \$key=>\$$result): ";
            $parseStr .= "++\$$key;\$mod = (\$$key % $mod );";
            $parseStr .= "if(!empty(\$$result) && \${$result} ['show']):  "; 
            $parseStr .= "?> $content  <?php endif;unset(\$$result);endforeach;?>";
        	$parseStr .= "<?php endif;?>";
        }
        return $parseStr;
    }
    
	//数据调用
    public function tagData($tag, $content){
		$time = strtotime(date('Y-m-d H:i',time()));
		$wheres = array('article'=>"state=1 and ctime<$time");
		$catid  = $tag['catid'];
		$table  = !empty($tag['table']) ? $tag['table'] : 'article';
        $alias	= !empty($tag['alias']) ? $tag['alias'] : 'a';
		$field  = !empty($tag['field']) ? $tag['field'] : '*';
		$order  = !empty($tag['order']) ? $tag['order'] : '';
		$join  =  !empty($tag['join']) ?  $tag['join'] : '';
		$group  = !empty($tag['group']) ? $tag['group'] : '';
		$limit  = !empty($tag['limit']) ? intval($tag['limit']) : 20;
		$result = !empty($tag['result']) ? $tag['result'] : 'vo';
        $cache  = !empty($tag['cache'])  ? intval($tag['cache']) : 0;
		$key   	= !empty($tag['key'])? $tag['key'] : 'i';
		$mod    = !empty($tag['mod']) ? $tag['mod'] : 2;
		$where  = isset($wheres[$table]) ? $wheres[$table]: '';
		$where  = empty($tag['where']) ? $where : (!empty($where)?$where.' and '.$tag['where'] : $tag['where']);
		//if(!empty($where)){
		//	$where = $this->parseCondition($where);
		//}
		if(!empty($join)){
			$join = str_replace('@_',C('DB.PREFIX'),$join);
		}
		$parseStr   = "<?php \$_DATA = M(\"$table\")->alias(\"$alias\")->field(\"$field\")->limit(\"$limit\")";
		if(!empty($join)) 		  		$parseStr .= "->join(\"$join\")";
		if(!empty($where)) 		  		$parseStr .= "->where(\"$where\")";
		if(!empty($order)) 		  		$parseStr .= "->order(\"$order\")";
		if(!empty($group)) 		  		$parseStr .= "->group(\"$group\")";
		if(!empty($cache)) 		  		$parseStr .= "->cache(true,$cache)";
		$parseStr .= "->select();";
		$parseStr .= "if(!empty(\$_DATA)):\${$key}=0;foreach(\$_DATA as \$key=>\${$result}): ";
		$parseStr .= "++\${$key};\$mod = (\${$key} % $mod);?>".$content;
		$parseStr .= "<?php unset(\${$result});endforeach; endif;?>";
		return $parseStr;
	}
	//友情链接
	public function tagLinks($tag,$content){
		$result   	= !empty($tag['result']) ? $tag['result']:'vo';
		$limit		= !empty($tag['limit'])  ? intval($tag['limit']):0;
        $key        = !empty($tag['key'])    ? $tag['key']:'i';
        $mod        = !empty($tag['mod'])    ? $tag['mod']:'2';
        $cache      = !empty($tag['cache'])  ? intval($tag['cache']) : 0;
		$type   	= !empty($tag['type'])   ? $tag['type']:'';
		$field  	= 'name,url,logo,ctime';
		$where 		= 'state=1';
		$parseStr   =   "<?php \$_LINKS = M(\"Links\")";
		if(!empty($tag['type'])) {
			$where = $tag['type']=='logo' ? $where." and logo!=''" : $where." and logo=''";
		}
		if(!empty($where))				$parseStr .= "->where(\"$where\")";
		if(!empty($cache)) 		  		$parseStr .= "->cache(true,$cache)";
		if(!empty($tag['limit']))		$parseStr .= "->limit(\"$limit\")";
		$parseStr .= "->select();";
		$parseStr .= "if(!empty(\$_LINKS)):\${$key}=0;foreach(\$_LINKS as \$key=>\${$result}): ";
		$parseStr .= "++\${$key};\$mod = (\${$key} % $mod);?>".$content;
		$parseStr .= "<?php unset(\${$result});endforeach; endif;?>";
		return $parseStr;
	}
	//广告位
	//@todo 支持随机
	public function tagPoster($tag,$content){
		$result   	= !empty($tag['result'])? $tag['result']:'vo';
		$limit		= !empty($tag['limit']) ? intval($tag['limit']):0;
        $key        = !empty($tag['key'])   ? $tag['key']:'i';
        $mod        = !empty($tag['mod'])   ? $tag['mod']:'2';
        $field      = !empty($tag['field']) ? $tag['field']:'id,title,url,image,bgcolor';
        $cache      = !empty($tag['cache']) ? intval($tag['cache']) : 0;
		$typeid   	= !empty($tag['typeid'])? intval($tag['typeid']) : 0;
		$random   	= !empty($tag['random'])? intval($tag['random']) : 0;
		$time		= TIME;
		$where 		= "state=1 and typeid=$typeid and (stime=0 or stime<$time) and (etime=0 or etime>$time)";
		$parseStr   = "<?php \$_POSTER = M(\"Poster\")->where(\"$where\")->field(\"$field\")";
		if(!empty($cache)) 		  		$parseStr .= "->cache(true,$cache)";
		if(!empty($tag['limit']))		$parseStr .= "->limit(\"$limit\")";
		$parseStr .= "->select();";
		$parseStr .= "if(!empty(\$_POSTER)):\${$key}=0;foreach(\$_POSTER as \$key=>\${$result}): ";
		$parseStr .= "++\${$key};\$mod = (\${$key} % $mod);?>".$content;
		$parseStr .= "<?php unset(\${$result});endforeach; endif;?>";
		return $parseStr;
	}
	//类别
	public function tagType($tag,$content){
		$catid 		= !empty($tag['catid']) ? $this->autoBuildVar($tag['catid']) : '';
		$pid		= isset($tag['pid']) ? intval($tag['pid']):null;
		$result   	= !empty($tag['result'])? $tag['result']:'vo';
		$model   	= !empty($tag['model']) ? $tag['model']:'article';
		$limit		= !empty($tag['limit']) ? intval($tag['limit']):0;
		$key        = !empty($tag['key'])   ? $tag['key']:'i';
		$mod        = !empty($tag['mod'])   ? $tag['mod']:'2';
        $cache      = !empty($tag['cache']) ? intval($tag['cache']) : 0;
		$where 		= $pid!=null?"state=1 and pid=$pid and model='$model'":"state=1 and model='$model'";
		$where 	   .= $catid ?" and catid=$catid":"";
		$parseStr   = "<?php \$_TYPE = M(\"Type\")->where(\"$where\")->field(\"id,name,pid,icon,remark\")";
		if(!empty($cache)) 		  		$parseStr .= "->cache(true,$cache)";
		if(!empty($tag['limit']))		$parseStr .= "->limit(\"$limit\")";
		$parseStr .= "->select();";
		$parseStr .= "\$_TYPE = array_to_tree(\$_TYPE,'id','pid');";
		$parseStr .= "if(!empty(\$_TYPE)):\${$key}=0;foreach(\$_TYPE as \$key=>\${$result}): ";
		$parseStr .= "++\${$key};\$mod = (\${$key} % $mod);?>".$content;
		$parseStr .= "<?php unset(\${$result});endforeach; endif;?>";
		return $parseStr;
	}
	
	

}