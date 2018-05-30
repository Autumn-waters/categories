<?php
namespace Qiuxinshu;
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
    public function insert_user($pid=0)
    {
        if($pid){
            $p_data = $this->model->find($pid);
            $le = $p_data->ri;
            $this->model->where('ri','>=',$le)->setInc('ri',2);
            $this->model->where('le','>',$le)->setInc('le',2);
            $ri = $le+1;
            $level = $p_data->level+1;
            //var_dump($level);exit;
            return $this->model->save(['le'=>$le,'ri'=>$ri,'pid'=>$pid,'level'=>$level]);
        }
        $ri = $this->model->max('ri');

        return $this->model->save(['le'=>$ri+1,'ri'=>$ri+2,'pid'=>0,'level'=>0]);

    }

    /*
     * 查询用户下级
     * @param $id用户
     * @param $level层级
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

        return $this->model->where($where)->select();

    }

    /*
     * 查询用户上级
     * */
    public function find_uper($id,$level=null)
    {
        $p_data = $this->model->find($id);
        $where = [
            ['le','<',$p_data->le],
            ['ri','>',$p_data->ri],
        ];
        if($level){
            $where[] = ['level','>=',$p_data->level-$level];
        }

        return $this->model->where($where)->select();

    }

    /*
     * 更改用户
     * @param $userid要更改的用户
     * @param $pid更改在哪个用户下
     * */
    public function update_user($userid,$pid)
    {
        $user = $this->model->find($userid);
        $p_data = $this->model->find($pid);

        $chirdArr = $this->find_chird_collection($user->le,$user->ri);

        $length = $user->ri-$user->le+1;

        $where = [
            ['ri','>',$user->ri],
            ['ri','<',$p_data->ri],
        ];

        $where1 = [
            ['le','>',$user->ri],
            ['le','<',$p_data->ri],
        ];
        $this->model->where($where1)->setDec('le',$length);
        $this->model->where($where)->setDec('ri',$length);


        $len = $this->model->where('id',$pid)->value('ri');
        $new_length = $len-$user->le-$length;

        $this->model->whereIn('id',$chirdArr)->setInc('le',$new_length);
        $this->model->whereIn('id',$chirdArr)->setInc('ri',$new_length);

        $level = $p_data->level+1;

        $new_level = $user->level-$level;

        $levelwhere = [
            ['le','>=',$user->le],
            ['ri','<=',$user->ri],
        ];
        $this->model->where($levelwhere)->setDec('level',$new_level);

        $this->model->where('id',$userid)->update(['pid'=>$pid,'level'=>$level]);
        //return true;
    }
    /*
     * 查询下级集合
     * @param $userid用户id
     * */

    public function find_chird_collection($le,$ri)
    {
        $where = [
            ['le','>=',$le],
            ['ri','<=',$ri],
        ];

        return $this->model->where($where)->column('id');
    }

}