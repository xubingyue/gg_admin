<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/22
 * Time: 9:47
 */
namespace Admin\Controller;
use Common\Controller\BaseController;
use MongoDB\BSON\ObjectID;
use Think\Page;

class MenuController extends BaseController {
    public function menusGet() {
        $admin_menu = $this->mongo_db->admin_menu;

        if (I('get._id')) {
            $search['_id'] = new ObjectID(I('get._id', null));
            $option['projection'] = array();
            $query = $admin_menu->findOne($search, $option);
            $this->_result['data']['menus'] = $query;
            $this->response($this->_result);

        } else {
            $search = array();
            $option['limit'] = intval(I('get.limit', C('PAGE_NUM')));
            $option['skip'] = (intval(I('get.p', 1)) - 1) * $option['limit'];
            filter_array_element($search);
            filter_array_element($option);

            $cursor = $admin_menu->find($search, $option);
            $result = array();
            foreach ($cursor as $item) {
                array_push($result, $item);
            }

            //查找parent menu
            $parent_query = $admin_menu->find(array("pid"=>'0'));
            $parent_result = array();
            foreach($parent_query as $item) {
                array_push($parent_result, $item);
            }

            $count = $admin_menu->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("menus", $result);
            $this->assign("parent_menu", $parent_result);
            $this->_result['data']['html'] = $this->fetch("menu:index");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['menus'] = $result;
            $this->_result['data']['parent_menu'] = $parent_result;

            $this->response($this->_result);
        }
    }

    public function menusPut() {

        $search['_id'] = new ObjectID(I('put._id'));
        $data['sort'] = intval(I('put.sort'));
        $data['name'] = I('put.name', null, check_empty_string);
        $data['action'] = I('put.action', 'javascript:void(0)');
        $data['icon'] = I('put.icon', 'fa-circle');
        $data['pid'] = I('put.pid', '0');

        merge_params_error($data['name'], 'name', '名称不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        filter_array_element($data);

        $update['$set'] = $data;
        $admin_menu = $this->mongo_db->admin_menu;
        if ($admin_menu->findOneAndUpdate($search,$update)) {
            $this->response($this->_result, 'json', 201, '保存成功');
        } else {
            $this->_result['data']['param'] = $data;
            $this->response($this->_result, 'json', 400, '保存失败');
        }

    }

    public function menusPost() {
        $data['sort'] = intval(I('post.sort'));
        $data['name'] = I('post.name', null, check_empty_string);
        $data['action'] = I('post.action', 'javascript:void(0)');
        $data['icon'] = I('post.icon', 'fa-circle');
        $data['pid'] = I('post.pid', '0');
        merge_params_error($data['name'], 'name', '名称不能为空', $this->_result['error']);

        //检查参数
        if ($this->_result['error']) {
            $error = array_shift($this->_result['error']);
            $error = array_values($error);
            $this->response($this->_result, 'json', 400, $error[0]);
        }
        filter_array_element($data);

        $admin_menu = $this->mongo_db->admin_menu;
        if ($admin_menu->InsertOne($data)) {
            $this->response($this->_result, 'json', 201, '新建成功');
        } else {
            $this->response($this->_result, 'json', 400, '新建失败');
        }
    }

    public function menusDelete() {
        $search['_id'] = new ObjectID(I('delete._id'));
        $admin_menu = $this->mongo_db->admin_menu;
        if ($admin_menu->deleteOne($search)) {
            $this->response($this->_result, 'json', 204, '删除成功');
        } else {
            $this->response($this->_result, 'json', 400, '删除失败');
        }
    }
}