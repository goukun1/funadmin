<?php
/**
 * FunAdmin
 * ============================================================================
 * 版权所有 2017-2028 FunAdmin，并保留所有权利。
 * 网站地址: http://www.FunAdmin.com
 * ----------------------------------------------------------------------------
 * 采用最新Thinkphp6实现
 * ============================================================================
 * Author: yuege
 * Date: 2017/8/2
 */

namespace app\backend\controller;

use app\backend\service\AddonService;
use app\common\controller\Backend;
use fun\helper\FileHelper;
use fun\addons\Service;
use think\App;
use think\Exception;
use app\common\model\Addon as AddonModel;
use app\common\annotation\ControllerAnnotation;
use app\common\annotation\NodeAnnotation;

/**
 * @ControllerAnnotation(title="插件管理")
 * Class Addon
 * @package app\backend\controller
 */
class Addon extends Backend
{
    protected $addonService;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->modelClass = new AddonModel();
        $this->addonService = new AddonService();

    }
    /**
     * @NodeAnnotation(title="列表")
     * @return mixed|\think\response\Json|\think\response\View
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $list = get_addons_list();
            $addons =  $this->modelClass->column('*', 'name');
            foreach ($list as $key => $value) {
                //是否已经安装过
                $config = get_addons_config($key);
                if ($addons && !isset($addons[$key]) || !$addons) {
                    $class = get_addons_instance($key);
                    $addons["$key"] = $class->getInfo();
                    if ($addons[$key]) {
                        $addons[$key]['install'] = 0;
                        $addons[$key]['status'] = 0;
                    }
                } else {
                    $addons[$key]['install'] = 1;
                }
                if(isset($config['domain']) && $config['domain']['value']){
                    $index = strpos($_SERVER['HTTP_HOST'],'.');
                    $addons[$key]['web'] = httpType().$config['domain']['value'].substr($_SERVER['HTTP_HOST'],$index);
                }else{
                    $addons[$key]['web'] = '/addons/'.$key;
                }
            }
            $result = ['code' => 0, 'msg' => lang('Delete Data Success'), 'data' => $addons, 'count' => count($addons)];
            return json($result);
        }
        return view();
    }
    /**
     * @NodeAnnotation(title="安装")
     * @throws Exception
     */
    public function install()
    {
        set_time_limit(0);
        $name = $this->request->param("name");
//        插件名是否为空
        if (!$name) {
            $this->error(lang('addon  %s can not be empty', [$name]));
        }
        //插件名是否符合规范
        if (!preg_match("/^[a-zA-Z0-9]+$/", $name)) {
            $this->error(lang('addon name inright'));
        }
        //检查插件是否安装
        if ($this->isInstall($name)) {
            $this->error(lang('addons %s is already installed', [$name]));
        }
        $class = get_addons_instance($name);
        if (empty($class)) {
            $this->error(lang('addons %s is not ready', [$name]));
        }
        //安装插件
        $class->install();
        // 安装菜单
        $menu_config=$this->get_menu_config($class);
        if(!empty($menu_config)){
            if(isset($menu_config['is_nav']) && $menu_config['is_nav']==1){
                $pid = 0;
            }else{
                $pid = $this->addonService->addAddonManager()->id;
            }
            $menu[] = $menu_config['menu'];
            $this->addonService->addAddonMenu($menu,$pid);
        }
        $addon_info = get_addons_info($name);
        $addon_info['status'] = 1;
        $res =  $this->modelClass->save($addon_info);
        if (!$res) {
            $this->error(lang('addon install fail'));
        }
        //添加数据库
        try{
            importsql($name);
        } catch (Exception $e){
            $this->error($e->getMessage());
        }
        $sourceAssetsDir = Service::getSourceAssetsDir($name);
        $destAssetsDir = Service::getDestAssetsDir($name);
        if (is_dir($sourceAssetsDir)) {
            FileHelper::copyDir($sourceAssetsDir, $destAssetsDir);
        }
        //复制文件到目录
        if(Service::getCheckDirs()){
            foreach (Service::getCheckDirs() as $k => $dir) {
                $sourcedir = Service::getAddonsNamePath($name). $dir;
                if (is_dir($sourcedir)) {
                    FileHelper::copyDir($sourcedir, app()->getRootPath().  $dir. DS .'static'.DS.'addons'.DS.$name);
                }
            }
        }
        try {
            Service::updateAddonsInfo($name);
            //刷新addon文件
            refreshaddons();
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }
        $this->success(lang('Install success'));
    }
    /**
     * @NodeAnnotation(title="卸载")
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function uninstall()
    {
        set_time_limit(0);
        $name = $this->request->param("name");
        if (!$name) {
            $this->error(lang(' addon name can not be empty'));
        }
        //插件名匹配
        if (!preg_match("/^[a-zA-Z0-9]+$/", $name)) {
            $this->error(lang('addon name is not right'));
        }
        //获取插件信息
        $info =  $this->modelClass->where('name', $name)->find();
        if (empty($info)) {
            $this->error(lang('addon is not exist'));
        }
        if($info->status==1){
            $this->error(lang('Please disable addons %s first',[$name]));
        }
        if (!$info->delete()) {
            $this->error(lang('addon uninstall fail'));
        }
        //卸载插件
        $class = get_addons_instance($name);
        $class->uninstall();
        //删除菜单
        $menu_config=$this->get_menu_config($class);
        try {
            if(!empty($menu_config)){
                $menu[] = $menu_config['menu'];
                $this->addonService->delAddonMenu($menu);
            }
            //卸载sql;
            uninstallsql($name);
        }catch (Exception $e){
            $this->error($e->getMessage());
        }
        // 移除插件基础资源目录
        $destAssetsDir = Service::getDestAssetsDir($name);
        if (is_dir($destAssetsDir)) {
            FileHelper::delDir($destAssetsDir);
        }
        //删除文件
        $list = Service::getGlobalAddonsFiles($name);
        foreach ($list as $k => $v) {
            @unlink(app()->getRootPath() . $v);
        }
        Service::updateAddonsInfo($name,1,0);
        try {
            //刷洗addon文件和配置
            refreshaddons();
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }
        $this->success(lang('Uninstall successful'));
    }

    /**
     * @NodeAnnotation (title="禁用启用")
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function modify()
    {
        $id = $this->request->param("id");
        $name = $this->request->param("name");

        if (!$id) {
            $this->error(lang('addon %s can not be empty', [$id]));
        }
        if (!preg_match("/^[a-zA-Z0-9]+$/", $name)) {
            $this->error(lang('addon name is not right'));
        }
        $info =  $this->modelClass->find($id);
        $addoninfo = get_addons_info($name);
        $addoninfo['status'] = $addoninfo['status']?0:1;
        try {
            $info->status =$addoninfo['status'];
            Service::updateAddonsInfo($name,$addoninfo['status']);
            refreshaddons();
            $info->save();
            $class = get_addons_instance($name);
            $addoninfo['status']==1 ?$class->enabled():$class->disabled();
        }catch (\Exception $e){
            $this->error(lang($e->getMessage()));
        }
        $this->success(lang('operation success'));
    }

    /**
     * @NodeAnnotation (title="插件配置")
     * @return \think\response\View
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function config()
    {
        $name = $this->request->get("name");
        $id = $this->request->get("id");
        $one =  $this->modelClass->find($id);
        $config = get_addons_config($name);
        if ($this->request->isAjax()) {
            $params = $this->request->param('params/a',[],'trim');
            if ($params) {
                foreach ($config as $k => &$v) {
                    if (isset($params[$k])) {
                        if ($v['type'] == 'array') {
                            $arr = [];
                            $params[$k] = is_array($params[$k]) ? $params[$k] :[];
                            foreach ($params[$k]['key'] as $kk=>$vv){
                                $arr[$vv] =  $params[$k]['value'][$kk];
                            }
                            $params[$k] = $arr;
                            $value = $params[$k];
                            $v['content'] = $value;
                            $v['value'] = $value;
                        } else {
                            $value =  $params[$k];
                        }
                        $v['value'] = $value;
                    }
                }
                unset($v);
                $config_data = json_encode($config,JSON_UNESCAPED_UNICODE);
                if($one->save(['config'=>$config_data])){
                    set_addons_config($name,$config);
                    refreshaddons();
                    $this->success(lang('operation success'));
                }else{
                    $this->error(lang('operation failed'));
                }
            }
            $this->error(lang('addon can not be empty'));
        }
        if (!$name) {
            $this->error(lang('addon name can not be empty'));
        }
        if (!preg_match("/^[a-zA-Z0-9]+$/", $name)) {
            $this->error(lang('addon name is not right'));
        }
        if (!$one) {
            $this->error(lang('addon config is not found'));
        }
        //模板引擎初始化
        $view = ['formData'=>$config,'title'=>$one->name];
        $configFile = app()->getRootPath() . 'addons' . DS . $name . DS . 'config.html';
        $viewFile = is_file($configFile) ? $configFile : '';
        return view($viewFile,$view);
    }

    /**
     * @NodeAnnotation (title="是否安装")
     * @param $name
     * @return array|false|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function isInstall($name)
    {
        if (empty($name)) {
            return false;
        }
        $addons =  $this->modelClass->where('name', $name)->find();
        return $addons;
    }
    /**
     * @NodeAnnotation (title="获取菜单",auth=false)
     * @param $class
     * @return mixed
     */
    protected function get_menu_config($class){
        $menu = $class->menu;
        return $menu;
    }




}
