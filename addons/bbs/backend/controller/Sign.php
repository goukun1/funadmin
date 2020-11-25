<?php
/**
 * lemocms
 * ============================================================================
 * 版权所有 2018-2027 lemocms，并保留所有权利。
 * 网站地址: https://www.lemocms.com
 * ----------------------------------------------------------------------------
 * 采用最新Thinkphp6实现
 * ============================================================================
 * Author: yuege
 * Date: 2019/8/2
 */
namespace app\admin\controller\ucenter;

use app\common\controller\Backend;
use app\common\model\BbsUserSignRule;
use think\facade\Request;
use think\facade\View;
use app\common\model\BbsUserSign ;

class Sign extends Backend
{

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    public function index()
    {
        if (Request::isPost()) {
            $keys = $this->request->post('keys', '', 'trim');
            $page = $this->request->post('page') ? $this->request->post('page') : 1;
            $list = BbsUserSign::alias('s')->join('user u','u.id=s.uid')->where('u.username|u.email', 'like', '%' . $keys . '%')
                ->field('s.*,u.username,u.email')
                ->order('s.id desc')
                ->paginate(['list_rows' => $this->pageSize, 'page' => $page])
                ->toArray();

            return $result = ['code' => 0, 'msg' => lang('get info success'), 'data' => $list['data'], 'count' => $list['total']];
        }

        return view();

    }

   
    public function delete()
    {
        $ids = $this->request->post('ids');
        if ($ids) {
            BbsUserSign::destroy($ids);
            $this->success(lang('delete success'));
        } else {
            $this->error(lang('delete fail'));

        }
    }

    public function state()
    {
        $id = $this->request->post('id');
        $data = $this->request->post();
        if ($id and $data['field']) {
            $model = new BbsUserSign();
            $model->state($data);
            $this->success(lang('edit success'));

        } else {
            $this->error(lang('edit fail'));

        }

    }

    public function rule(){

        if (Request::isPost()) {
            $keys = $this->request->post('keys', '', 'trim');
            $page = $this->request->post('page') ? $this->request->post('page') : 1;
            $list = BbsUserSignRule::where('days', 'like', '%' . $keys . '%')
                ->order('id desc')
                ->paginate(['list_rows' => $this->pageSize, 'page' => $page])
                ->toArray();

            return $result = ['code' => 0, 'msg' => lang('get info success'), 'data' => $list['data'], 'count' => $list['total']];
        }

        return view();

    }

    public function ruleAdd(){

        if (Request::isPost()) {
            $data = $this->request->post();

            $result = BbsUserSignRule::create($data);
            if ($result) {
                $this->success(lang('add success'), url('index'));
            } else {
                $this->error(lang('add fail'));
            }
        } else {
            $info = '';
            $view = [
                'info'  =>$info,
                'title' => lang('add'),
            ];
            View::assign($view);
            return view();
        }

    }

    public function ruleEdit(){

        if (Request::isPost()) {
            $data = $this->request->post();
            //添加
            $result = BbsUserSignRule::update($data);
            if ($result) {
                $this->success(lang('add success'), url('rule'));
            } else {
                $this->error(lang('add fail'));
            }
        } else {
            $info = BbsUserSignRule::find(input('id'));
            $view = [
                'info'  =>$info,
                'title' => lang('edit'),
            ];
            View::assign($view);
            return view('rule_add');
        }

    }

    public function ruleDel(){

        $ids = $this->request->post('ids');
        if ($ids) {
            $model = new BbsUserSignRule();
            $model->del($ids);
            $this->success(lang('delete success'));
        } else {
            $this->error(lang('delete fail'));

        }

    }
    public function ruleState(){

        $id = $this->request->post('id');
        $data = $this->request->post();
        if ($id and $data['field']) {
            $model = new BbsUserSignRule();
            $model->state($data);
            $this->success(lang('edit success'));

        } else {
            $this->error(lang('edit fail'));

        }


    }

}