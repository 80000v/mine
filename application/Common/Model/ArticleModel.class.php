<?php
namespace Common\Model;
use Common\Model\CommonModel;
class ArticleModel extends CommonModel{
	protected $autoCheckFields = false;
	/*
	 * 文章分类
	 * @param  $term_id 文章分类ID
	 * @return  array
	 * */
	public function getArticleList($term_id,$limit=NULL){
		$join = "".C('DB_PREFIX').'posts as b on a.object_id =b.id';
		$rs= M("TermRelationships");
		$order = 'post_date desc';
		$field = 'id,post_title,post_excerpt,post_content,post_modified';
		if(empty($limit)){
			$limit = '';
		}
		$where['status'] = array('eq',1);
		$where['post_status'] = array('eq',1);
		$where['term_id'] = array('eq',$term_id);
		$posts=$rs->alias("a")->join($join)->field($field)->where($where)->order($order)->limit($limit)->select();
		if($posts){
			return $posts;
		}else{
			return null;
		}
	}
	/*
	 * 文章内容
	 * @param $id 文章id
	 * @return array
	 * */
	public function getContent($id){
		$result = M('posts')->where("id="."'$id'")->field("post_title,post_content,post_date")->find();
		if($result){
			return $result;
		}else{
			return false;
		}
	}
}