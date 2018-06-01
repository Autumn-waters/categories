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
            $parent = $this->model->find($pid);

            $le = $parent->ri;

            $this->model->where('ri','>=',$le)->setInc('ri',2);

            $this->model->where('le','>',$le)->setInc('le',2);

            return $this->model->save(['le'=>$le,'ri'=>$le+1,'pid'=>$pid,'level'=>$parent->level+1]);

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
        $member = $this->model->find($id);
        $where = [
            ['le','>',$member->le],
            ['ri','<',$member->ri]
        ];
        if($level){
            $where[] = ['level','<=',$member->level+$level];
        }

        return $this->model->where($where)->select();

    }

    /*
     * 查询用户上级
     * */
    public function find_uper($id,$level=null)
    {
        $member = $this->model->find($id);
        $where = [
            ['le','<',$member->le],
            ['ri','>',$member->ri],
        ];
        if($level){
            $where[] = ['level','>=',$member->level-$level];
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
        $member = $this->model->find($userid);
        $parent = $this->model->find($pid);

        $chirdArr = $this->find_chird_collection($member->le,$member->ri);

        $length = $member->ri-$member->le+1;

        if($parent->ri-$member->ri>0){
            $this->update_right_user($member,$parent,$length,$chirdArr);
        }else{
            $this->update_left_user($member,$parent,$length,$chirdArr);
        }

        $level = $parent->level+1;

        $new_level = $member->level-$level;

        $levelwhere = [
            ['le','>=',$member->le],
            ['ri','<=',$member->ri],
        ];
        $this->model->where($levelwhere)->setDec('level',$new_level);

        $this->model->where('id',$userid)->update(['pid'=>$pid,'level'=>$level]);
    }
    /*
     * 更改到右边用户
     * @param $userid要更改的用户
     * @param $pid更改在哪个用户下
     * */
    public function update_right_user($member,$parent,$length,$chirdArr)
    {
        $where = [
            ['ri','>',$member->ri],
            ['ri','<',$parent->ri],
        ];

        $where1 = [
            ['le','>',$member->ri],
            ['le','<',$parent->ri],
        ];
        $this->model->where($where1)->setDec('le',$length);
        $this->model->where($where)->setDec('ri',$length);

        $new_length = $parent->ri-$member->le-$length;

        $this->model->whereIn('id',$chirdArr)->setInc('le',$new_length);
        $this->model->whereIn('id',$chirdArr)->setInc('ri',$new_length);


        //return true;
    }

    /*
     * 更改到左边的用户
     * @param $userid要更改的用户
     * @param $pid更改在哪个用户下
     * */
    public function update_left_user($member,$parent,$length,$chirdArr)
    {

        $where = [
            ['ri','<',$member->ri],
            ['ri','>=',$parent->ri],
        ];

        $where1 = [
            ['le','<',$member->le],
            ['le','>',$parent->ri],
        ];
        $this->model->where($where1)->setInc('le',$length);
        $this->model->where($where)->setInc('ri',$length);

        $new_length = $parent->ri-$member->le;

        $this->model->whereIn('id',$chirdArr)->setInc('le',$new_length);
        $this->model->whereIn('id',$chirdArr)->setInc('ri',$new_length);
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