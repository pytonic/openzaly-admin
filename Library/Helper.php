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

use Library\Plugin\ConfigKey;

class Helper
{
    /**
     * 解析获取proxyPackage数据
     *
     * @author 尹少爷 2018.1.4
     *
     * @param string params
     *
     * @return array['site_user_id' => '', 'data' => []]
     *
     */
    public static function getDataFromProxy($params)
    {
        $log     = \Zaly\Log::init();
        $logText = [
            'msg' => 'get params from proxy',
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $proxyPackage    = new \Library\Plugin\ProxyPackage();
            $proxyPackage->mergeFromString($params);
            $currentUserMap  = $proxyPackage->getProxyContent();
            $siteUserId = 0;
            foreach ($currentUserMap as $key => $currentUserId) {
                $siteUserId = $currentUserId;
            }
            $data    = $proxyPackage->getdata();
            $datas   = json_decode($data, true);
            $output  = ['site_user_id' => $siteUserId, 'data' => $datas];
            $log->info(['recevie_data' => $output]);
            return $output;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return '';
        }
    }

    /**
     * 生成proxyPackage数据
     *
     * @author 尹少爷 2018.1.4
     *
     * @param string params
     *
     * @return array['site_user_id' => '', 'data' => []]
     *
     */
    public static function generateDataForProxy($siteUserId, $content)
    {
        $log = \Zaly\Log::init();
        $logText = [
            'msg'          => 'generate data for proxy',
            'method'       => __METHOD__,
            'params'       => $content,
            'site_user_id' => $siteUserId,
        ];
        try {
            $log->info($logText);
            $proxyPackage    = new \Library\Plugin\ProxyPackage();
            $proxyPackage->setData($content);
            $proxyPackage->setProxyContent(['1' => $siteUserId]);
            $proxyPackage = $proxyPackage->serializeToString();
            return $proxyPackage;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return '';
        }
    }
    /**
     * 解析获取pluginPackage数据
     *
     * @author 尹少爷 2018.1.4
     *
     * @param string params
     *
     * @return array['error' => '', 'data' => []]
     *
     */
    public static function getDataFromPlugin($params)
    {
        $log = \Zaly\Log::init();
        $logText = [
            'msg'    => 'get params from plugin',
            'method' => __METHOD__,
        ];

        try {
            $log->info($logText);
            $pluginPackage  = new \Library\Plugin\PluginPackage();
            $pluginPackage->mergeFromString($params);
            $errorInfo = $pluginPackage->getErrorInfo();
            if ($errorInfo) {
                $errorCode = $errorInfo->getCode();
                if ($errorCode == 'success') {
                    $data = $pluginPackage->getData();
                    $output = ['error' => 'success', 'data' => $data];
                    return $output;
                }
                throw new \Exception('get data failed');
            }
            $output = ['error' => 'success', 'data' => []];
            return $output;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return ["error" => 'fail'];
        }
    }
    //校验用户是不是管理员
    public static function judgeIsAdmin($params, $getConfigUrl)
    {
        $log = \Zaly\Log::init();
        $logText = [
            'msg'    => 'judgment admin',
            'method' => __METHOD__,
            'params' => $params

        ];
        try {
            $log->info($logText);
            if ($params && strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
                $result = Helper::getDataFromProxy($params);
                $siteUserId = $result['site_user_id'];
            } elseif (!$params && strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') {
                $siteUserId = isset($_GET['siteUserId']) ? $_GET['siteUserId'] : '';
            }
            if (!isset($siteUserId) || !$siteUserId) {
                $log->info('no siteUserId, Permission denied');
                throw new \Exception('Permission denied');
            }
            $configReq = new \Library\Plugin\HaiSiteGetConfigRequest();
            $configReq = $configReq->serializeToString();
            $configReq = Helper::generateDataForProxy($siteUserId, $configReq);

            $curl   = \Zaly\Curl::init();
            $result = $curl->request('post', $getConfigUrl, $configReq);
            $result = Helper::getDataFromPlugin($result);
            if ($result['error'] == 'fail') {
                throw new \Exception('get data failed');
            }
            $data = $result['data'];
            $configRep = new \Library\Plugin\HaiSiteGetConfigResponse();
            $configRep->mergeFromString($data);
            $configObjs = $configRep->getSiteConfig();
            if (!$configObjs) {
                throw new \Exception('get config failed');
            }
            $configObjs = $configObjs->getSiteConfig();
            $adminId = isset($configObjs[ConfigKey::SITE_ADMIN]) ? $configObjs[ConfigKey::SITE_ADMIN] : '' ;
            if (($adminId == 'ZALY_SHAOYE') ||( $adminId === $siteUserId)) {
                return true;
            }
            return false;
        } catch (\Exception $ex) {
            $log->error($params);
            return false;
        }
    }
}
