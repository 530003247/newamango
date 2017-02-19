<?php
// +----------------------------------------------------------------------
// | Amango [ 芒果一站式微信营销系统 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.Amango.net All rights reserved.
// +----------------------------------------------------------------------
// | Author: ChenDenlu <530003247@vip.qq.com>
// +----------------------------------------------------------------------
namespace Common\Api;
class CategoryApi {
    //获取分类下的浏览量和帖子数量
    public static function get_category_condition($id){
        $pid = get_category($id, 'pid');
        //如果是顶级
        if($pid==0){
            return array(9999,99999);
        } else {
            //非顶级
            $viewslist = M('Document')->where(array('category_id'=>$id))->field('view')->select();
            $i = 0;
            foreach ($viewslist as $key => $value) {
                $i = $i+$value['view'];
            }
            return array(count($viewslist),$i);
        }
    }
    //获取分类所属的模型
    public static function get_category_model($id,$type='title'){
        $parenmodel = get_category($id, 'model');
        $model      = $parenmodel[0];
        if($type=='title'){
            $name = M('Model')->where(array('id'=>$model))->getField('name');
            return $name;
        } else {
            return $model;
        }
    }
    /* 根据ID获取上级分类模板 逐层递进 如果子分类模板不存在 读取父分类  直至list  目前仅支持三层*/
    public static function get_category_template($id){
        if($id==0){
            return '';
        } else {
            $selftpl   = get_category($id, 'template_lists');
            return $selftpl;
        }
    }
    /**
     * 获取分类信息并缓存分类
     * @param  integer $id    分类ID
     * @param  string  $field 要获取的字段名
     * @return string         分类信息
     */
    public static function get_category($id, $field = null){
        static $list;

        /* 非法分类ID */
        if(empty($id) || !is_numeric($id)){
            return '';
        }

        /* 读取缓存数据 */
        if(empty($list)){
            $list = S('sys_category_list');
        }

        /* 获取分类名称 */
        if(!isset($list[$id])){
            $cate = M('Category')->find($id);
            if(!$cate || 1 != $cate['status']){ //不存在分类，或分类被禁用
                return '';
            }
            $list[$id] = $cate;
            S('sys_category_list', $list); //更新缓存
        }
        return is_null($field) ? $list[$id] : $list[$id][$field];
    }

    /* 根据ID获取分类标识 */
    public static function get_category_name($id){
        return get_category($id, 'name');
    }

    /* 根据ID获取分类名称 */
    public static function get_category_title($id){
        return get_category($id, 'title');
    }
    //芒果   新增
    /* 根据ID获取分类分类下文章列表 */
    public static function get_category_list($cateid,$field,$order){
            $field    = empty($field) ? 'l.id,l.title' : $field;
            $order    = empty($order) ? 'l.level DESC,l.id DESC' : $order;
            $nowtime  = time();
            $options  = get_category($cateid);
            $prefix   = C('DB_PREFIX');
            $l_table  = $prefix.('document');
            $r_table  = $prefix.strtolower(get_table_name($options['model']));
            //联合查询
            $lists = M() ->table( $l_table.' l,'.$r_table.' r' )
                         ->where('l.id=r.id AND l.status=1 AND l.category_id='.$cateid.' AND l.create_time<='.$nowtime.' AND l.deadline>='.$nowtime.' AND l.model_id='.$options['model'])
                         ->field($field)
                         ->order($order)
                         ->select();
            return $lists;
    }

    /* 获取分类下的 随机帖子  数目*/
    public static function get_category_rand($cateid,$field,$limit){
        $newfield     = explode(',', $field);
        $defaultfield = array('uid','name','title','description','cover_id','view','comment');
        array_map(function($a) use($defaultfield, &$field){
            $fieldpre = in_array($a, $defaultfield) ? 'l.' : 'r.';
            $field    = str_replace($a, $fieldpre.$a, $field);
        },$newfield);

            $limit    = empty($limit) ? 1 : $limit;
            $nowtime  = time();
            $options  = get_category($cateid);
            $prefix   = C('DB_PREFIX');
            $l_table  = $prefix.('document');
            $r_table  = $prefix.strtolower(get_table_name($options['model']));
            //联合查询
            $lists = M() ->table( $l_table.' l,'.$r_table.' r' )
                         ->where('l.id=r.id AND l.status=1 AND l.category_id='.$cateid.' AND l.create_time<='.$nowtime.' AND l.deadline>='.$nowtime.' AND l.model_id='.$options['model'])
                         ->field($field.',l.id')
                         ->order('rand()')
                         ->limit($limit)
                         ->select();
            return $lists;
    }

    //芒果   新增   暂不采用联表查询  避免超时
    /* 获取分类下的 最新帖子  数目*/
    public static function get_category_news($cateid,$field,$limit){
            $newfield     = explode(',', $field);
            $defaultfield = array('uid','name','title','description','cover_id','view','comment');
               array_map(function($a) use($defaultfield, &$field){
                                $fieldpre = in_array($a, $defaultfield) ? 'l.' : 'r.';
                                $field    = str_replace($a, $fieldpre.$a, $field);
                         },$newfield);
        $limit    = empty($limit) ? 1 : $limit;
            $nowtime  = time();
            $options  = get_category($cateid);
            $prefix   = C('DB_PREFIX');
            $l_table  = $prefix.('document');
            $r_table  = $prefix.strtolower(get_table_name($options['model']));
            //联合查询
            $lists = M() ->table( $l_table.' l,'.$r_table.' r' )
                         ->where('l.id=r.id AND l.status=1 AND l.category_id='.$cateid.' AND l.create_time<='.$nowtime.' AND l.deadline>='.$nowtime.' AND l.model_id='.$options['model'])
                         ->field($field.',l.id')
                         ->order('l.level DESC,l.id DESC')
                         ->limit($limit)
                         ->select();
            return $lists;
    }

    /**
     * 获取参数的所有父级分类
     * @param int $cid 分类id
     * @return array 参数分类和父类的信息集合
     * @author huajie <banhuajie@163.com>
     */
    public static function get_parent_category($cid){
        if(empty($cid)){
            return false;
        }
        $cates  =   M('Category')->where(array('status'=>1))->field('id,title,pid')->order('sort')->select();
        $child  =   get_category($cid);	//获取参数分类的信息
        $pid    =   $child['pid'];
        $temp   =   array();
        $res[]  =   $child;
        while(true){
            foreach ($cates as $key=>$cate){
                if($cate['id'] == $pid){
                    $pid = $cate['pid'];
                    array_unshift($res, $cate);	//将父分类插入到数组第一个元素前
                }
            }
            if($pid == 0){
                break;
            }
        }
        return $res;
    }
    /**
     * 与同级所有分类信息
     * @param int $id 分类id
     * @return array 参数分类和父类的信息集合
     */
    public static function get_all_category($id){
        static $all_category;
        if(!isset($all_category[$id])){
            $pid = self::get_category($id,'pid');
            if($pid==0){
                $all_category[$id] = self::get_category_group($id);
            } else {
                $all_category[$id] = self::get_category_group($pid);
            }
        }
        return $all_category[$id];
    }
    /**
     * 与同级所有分类信息
     * @param int $id 分类id
     * @return array 参数分类和父类的信息集合
     */
    public static function get_category_group($id){
        static $categorygroup;
        if(!isset($categorygroup[$id])){
            $categorygroup[$id] = M('Category')->where(array('pid'=>$id))->select();
        }
        return $categorygroup[$id];
    }
}