<?php
namespace qiuxinshu;
use think\Db;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/28
 * Time: 15:18
 */
class Category
{
    protected $model;

    public function __construct($table)
    {
        $this->model = new $table;
    }

    /*
     * 绑定数据库模型
     * */
    public function bind()
    {
        return $this->model;
    }


    /*
     * 用户添加方法
     * @param $le区间左边
     * @param $ri区间右边
     * @param $level用户层级
     * @param $pid上级用户
     * */
    public function insert_user($pid)
    {
        if(isset($pid) || $pid){
            $p_data = $this->model->find($pid);
            $le = $p_data->ri;
            $this->model->where('ri','>=',$le)->setInc('ri',2);
            $this->model->where('le','>',$le)->setInc('left',2);
            $ri = $le+1;
            $level = $p_data->level+1;
            return $this->model->save(['le'=>$le,'ri'=>$ri,'pid'=>$pid,'level'=>$level]);
        }
        $ri = $this->model->max('ri');

        return $this->model->save(['le'=>$ri+1,'ri'=>$ri+2,'pid'=>0,'level'=>0]);

    }

    /*
     * 查询用户下级
     * @param $id用户
     * $level
     * */
    public function find_lower($id,$level=null)
    {
        $p_data = $this->model->find($id);
        $where = [
            ['le','>',$p_data->le],
            ['ri','<',$p_data->ri]
        ];
        if($level){
            $where[] = ['level','<=',$p_data->level+$level];
        }

        $this->model->where($where)->select();

    }


}