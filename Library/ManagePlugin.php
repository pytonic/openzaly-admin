<?php
/**
 * opyright 2018-2019 Akaxin Group

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License ata

 *   http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Library;

use Library\Plugin\HaiPluginListRequest;
use Library\Plugin\HaiPluginListResponse;
use Library\Plugin\HaiPluginDeleteRequest;
use Library\Plugin\PluginProfile;
use Library\Plugin\PluginStatus;
use Library\Plugin\HaiPluginAddRequest;
use Library\Plugin\HaiPluginUpdateRequest;
use Library\Plugin\HaiPluginProfileRequest;
use Library\Plugin\HaiPluginProfileResponse;

use Zaly\Curl;
use Zaly\Log;
use Library\Helper;

class ManagePlugin
{
    /**
     * 基本设置-获取插件列表
     *
     * @author 尹少爷 2018.1.11
     *
     * @param proto $result
     *
     */
    public static function pluginList($params, $pluginListUrl, $pageSize = 12)
    {
        $log     = Log::init();
        $loading = true;
        $logText = [
            'msg'    => 'get plugin list',
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $result     = Helper::getDataFromProxy($params);
            $siteUserId = isset($result['site_user_id']) ? $result['site_user_id']: '';
            $page       = isset($result['data']['page']) ? $result['data']['page'] : 1;

            $pluginReq = new HaiPluginListRequest();
            $pluginReq->setPageNumber($page);
            $pluginReq->setPageSize($pageSize);
            $pluginReq->setStatus(PluginStatus::ALL_PLUGIN);
            $pluginReq = $pluginReq->serializeToString();
            $pluginReq = Helper::generateDataForProxy($siteUserId, $pluginReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $pluginListUrl, $pluginReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('get plugin list failed');
            }
            $data        = $results['data'];
            $pluginRep   = new HaiPluginListResponse();
            $pluginRep->mergeFromString($data);
            $pluginLists = $pluginRep->getPlugin();

            $lists = [];
            foreach ($pluginLists as $key => $plugin) {
                $lists[$key]['id']          = $plugin->getId();
                $lists[$key]['name']        = $plugin->getName();
                $lists[$key]['url_page']    = $plugin->getUrlPage();
                $lists[$key]['url_api']     = $plugin->getUrlApi();
                $lists[$key]['plugin_icon'] = $plugin->getIcon();
                $lists[$key]['status']      = $plugin->getStatus();
            }
            if (count($lists) >= 12) {
                $loading = false;
            }
            $output = ['results' => $lists, 'loading' => $loading];
            $log->info(['return_results'=> $output]);
            return $output;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return ['results' => [], 'loading' => $loading];
        }
    }

    /**
     * 基本设置-删除插件
     *
     * @author 尹少爷 2018.1.11
     *
     * @param proto $result
     *
     */
    public static function deletePlugin($params, $delPluginUrl)
    {
        $log = Log::init();
        $logText = [
            'msg'    => 'delete plugin',
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $result = Helper::getDataFromProxy($params);
            $pluginId     = isset($result['data']['plugin_id']) ? $result['data']['plugin_id'] : '';
            $siteUserId   = $result['site_user_id'];
            $delPluginReq = new HaiPluginDeleteRequest();
            $delPluginReq->setPluginId($pluginId);
            $delPluginReq = $delPluginReq->serializeToString();
            $delPluginReq = Helper::generateDataForProxy($siteUserId, $delPluginReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $delPluginUrl, $delPluginReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('delete plugin failed');
            }
            $log->info(['return_results' => 'success']);
            return 'success';
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return 'fail';
        }
    }

    /**
     * 基本设置-更新插件
     *
     * @author 尹少爷 2018.1.11
     *
     * @param proto $result
     *
     */
    public static function updatePlugin($params, $updatePluginUrl)
    {
        $log = Log::init();
        $logText = [
            'msg'    => 'update plugin',
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $result = Helper::getDataFromProxy($params);
            $siteUserId = $result['site_user_id'];
            $pluginId   = isset($result['data']['plugin_id']) ? $result['data']['plugin_id'] : '';
            $urlPage    = isset($result['data']['url_page']) ? $result['data']['url_page'] : '';
            $name       = isset($result['data']['name']) ? $result['data']['name'] : '';
            $urlApi     = isset($result['data']['url_api'])? $result['data']['url_api'] :'';
            $status     = isset($result['data']['plugin_status']) ? $result['data']['plugin_status'] : 0;
            $pluginIcon = isset($result['data']['plugin_icon']) ? $result['data']['plugin_icon'] : '';
            $status = constant(PluginStatus::class.'::'.strtoupper($status));

            $pluginProfile = new PluginProfile();
            $pluginProfile->setStatus($status);
            $pluginProfile->setId($pluginId);
            $pluginProfile->setName($name);
            $pluginProfile->setUrlPage($urlPage);
            $pluginProfile->setUrlApi($urlApi);
            $pluginProfile->setIcon($pluginIcon);
            $upPluginReq = new HaiPluginUpdateRequest();
            $upPluginReq->setPlugin($pluginProfile);
            $upPluginReq = $upPluginReq->serializeToString();
            $upPluginReq = Helper::generateDataForProxy($siteUserId, $upPluginReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $updatePluginUrl, $upPluginReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('update plugin info failed');
            }
            $log->info(['return_results' => 'success']);
            return 'success';
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return 'fail';
        }
    }
    /**
     * 基本设置-添加插件
     *
     * @author 尹少爷 2018.1.11
     *
     * @param proto $result
     *
     */
    public static function addPlugin($params, $pluginAddUrl)
    {
        $log = Log::init();
        $logText = [
            'msg'    => 'add plugin',
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $result     = Helper::getDataFromProxy($params);
            $siteUserId = $result['site_user_id'];
            $name       = isset($result['data']['name']) ? $result['data']['name'] : '';
            $urlPage    = isset($result['data']['url_page']) ? $result['data']['url_page'] : '';
            $urlApi     = isset($result['data']['url_api']) ? $result['data']['url_api'] : '';
            $pluginIcon = isset($result['data']['plugin_icon']) ? $result['data']['plugin_icon'] : '';

            $pluginProfile = new PluginProfile();
            $pluginProfile->setName($name);
            $pluginProfile->setUrlPage($urlPage);
            $pluginProfile->setUrlApi($urlApi);
            $pluginProfile->setIcon($pluginIcon);
            $pluginProfile->setStatus(PluginStatus::DISABLED);
            $pluginAddReq = new HaiPluginAddRequest();
            $pluginAddReq->setPlugin($pluginProfile);
            $pluginAddReq = $pluginAddReq->serializeToString();
            $pluginAddReq = Helper::generateDataForProxy($siteUserId, $pluginAddReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $pluginAddUrl, $pluginAddReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('添加插件失败');
            }
            $log->info(['return_results' => $results]);
            return 'success';
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return 'fail';
        }
    }
    /**
     * 基本设置-获取插件信息
     *
     * @author 尹少爷 2018.1.11
     *
     * @param proto $result
     *
     */
    public static function pluginInfo($params, $pluginInfoUrl)
    {
        $log = Log::init();
        $logText = [
            'msg'    => 'get plugin info',
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $result        = Helper::getDataFromProxy($params);
            $pluginId      = isset($result['data']['plugin_id']) ? $result['data']['plugin_id'] : '';
            $siteUserId    = $result['site_user_id'];
            $pluginInfoReq = new HaiPluginProfileRequest();
            $pluginInfoReq->setPluginId($pluginId);
            $pluginInfoReq = $pluginInfoReq->serializeToString();
            $pluginInfoReq = Helper::generateDataForProxy($siteUserId, $pluginInfoReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $pluginInfoUrl, $pluginInfoReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('get plugin info failed');
            }
            $data          = $results['data'];
            $pluginInfoRep = new HaiPluginProfileResponse();
            $pluginInfoRep->mergeFromString($data);
            $pluginInfo    = $pluginInfoRep->getPlugin();

            $lists = [];
            $lists['id']            = $pluginInfo->getId();
            $lists['name']          = $pluginInfo->getName();
            $lists['url_page']      = $pluginInfo->getUrlPage();
            $lists['url_api']       = $pluginInfo->getUrlApi();
            $lists['plugin_icon']   = $pluginInfo->getIcon();
            $lists['plugin_status'] = $pluginInfo->getStatus();

            $log->info(['return_results' => $lists]);
            return $lists;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return [];
        }
    }
}
