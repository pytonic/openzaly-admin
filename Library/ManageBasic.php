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

use Library\Plugin\ProxyPackage;
use Library\Plugin\ConfigKey;
use Library\Plugin\PluginPackage;
use Library\Plugin\HaiSiteGetConfigRequest;
use Library\Plugin\HaiSiteUpdateConfigRequest;
use Library\Plugin\HaiSiteUpdateConfigResponse;
use Library\Plugin\HaiSiteGetConfigResponse;
use Library\Plugin\SiteBackConfig;
use Library\Plugin\U2EncryptionStatus;
use Library\Plugin\RegisterWay;
use Zaly\Curl;
use Zaly\Log;
use Zaly\Config;
use Library\Helper;
use Library\SiteMemberLists;

class ManageBasic
{
    /**
     * 基本设置-获取基础设置配置
     *
     * @author 尹少爷 2018.1.4
     *
     * @param proto $result
     *
     */
    public static function getBasicConfig($params, $getConfigUrl)
    {
        $log = Log::init();
        $logText = [
            'msg'    => 'get site basic',
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $result     = Helper::getDataFromProxy($params);
            $siteUserId = $result['site_user_id'];
            $configReq  = new HaiSiteGetConfigRequest();
            $configReq  = $configReq->serializeToString();
            $configReq  = Helper::generateDataForProxy($siteUserId, $configReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $getConfigUrl, $configReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('get data error');
            }
            $data = $results['data'];
            $siteConfig = [
                'site_ip'              => '',
                'site_port'            => '',
                'site_http_port'       => '',
                'site_http_address'    => '',
                'site_name'            => '',
                'site_logo'            => '',
                'site_desc'            => '',
                'site_reister_way'     => 0,
                'pic_size'             => 1,
                'pic_path'             => '/akaxin',
                'group_members_count'  => 100,
                'u2_encryption_status' => 1
            ];
            if (isset($data)) {
                $configRep = new HaiSiteGetConfigResponse();
                $configRep->mergeFromString($data);
                $configObj = $configRep->getSiteConfig();
                if (!$configObj) {
                    $log->info(['not_results_return_default' => $siteConfig]);
                    return $siteConfig;
                }
                $configObjs = $configObj->getSiteConfig();
                $siteConfig = [
                    'site_ip'              => isset($configObjs[ConfigKey::SITE_ADDRESS]) ? $configObjs[ConfigKey::SITE_ADDRESS] : '',
                    'site_port'            => isset($configObjs[ConfigKey::SITE_PORT]) ? $configObjs[ConfigKey::SITE_PORT] : '',
                    'site_http_address'    => isset($configObjs[ConfigKey::SITE_HTTP_ADDRESS]) ? $configObjs[ConfigKey::SITE_HTTP_ADDRESS] : '',
                    'site_http_port'       => isset($configObjs[ConfigKey::SITE_HTTP_PORT]) ? $configObjs[ConfigKey::SITE_HTTP_PORT] : '',
                    'site_name'            => isset($configObjs[ConfigKey::SITE_NAME]) ? $configObjs[ConfigKey::SITE_NAME] : '',
                    'site_logo'            => isset($configObjs[ConfigKey::SITE_LOGO]) ? $configObjs[ConfigKey::SITE_LOGO] : '',
                    'site_desc'            => isset($configObjs[ConfigKey::SITE_INTRODUCTION]) ? $configObjs[ConfigKey::SITE_INTRODUCTION] : '',
                    'site_reister_way'     => isset($configObjs[ConfigKey::REGISTER_WAY]) ? $configObjs[ConfigKey::REGISTER_WAY] : 0,
                    'pic_size'             => isset($configObjs[ConfigKey::PIC_SIZE]) ? $configObjs[ConfigKey::PIC_SIZE] : 1,
                    'pic_path'             => isset($configObjs[ConfigKey::PIC_PATH]) ? $configObjs[ConfigKey::PIC_PATH] : '/akaxin',
                    'group_members_count'  => isset($configObjs[ConfigKey::GROUP_MEMBERS_COUNT]) ? $configObjs[ConfigKey::GROUP_MEMBERS_COUNT] : 100,
                    'u2_encryption_status' => isset($configObjs[ConfigKey::U2_ENCRYPTION_STATUS]) ? $configObjs[ConfigKey::U2_ENCRYPTION_STATUS] : 1,
                ];
            }
            $log->info(['return_results' => $siteConfig]);
            return $siteConfig;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return false;
        }
    }
    /**
     * 基本设置-修改基础设置配置
     *
     * @author 尹少爷 2018.1.4
     *
     * @param proto $result
     *
     */
    public static function setBasicConfig($params, $setSiteConfigUrl)
    {
        $log = Log::init();
        $logText = [
            'msg'    => 'get site basic',
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $result     = Helper::getDataFromProxy($params);
            $siteUserId = $result['site_user_id'];
            $data       = $result['data'];
            $upData     = [];
            foreach ($data as $key => $val) {
                $val = mb_convert_encoding($val, 'utf-8');
                $keyname = constant(ConfigKey::class.'::'.strtoupper($key));
                if ($key != 'register_way' && $key != 'u2_encryption_status') {
                    $upData[$keyname] = $val;
                }
                if ($key == 'register_way') {
                    $val = constant(RegisterWay::class.'::'.strtoupper($val));
                    $upData[$keyname] = $val;
                }
                if ($key == 'u2_encryption_status') {
                    $val = constant(U2EncryptionStatus::class.'::'.strtoupper($val));
                    $upData[$keyname] = $val;
                }
            }

            $siteBackConfig = new SiteBackConfig();
            $siteBackConfig->setSiteConfig($upData);
            $configReq      = new HaiSiteUpdateConfigRequest();
            $configReq->setSiteConfig($siteBackConfig);
            $configReq      = $configReq->serializeToString();
            $configReq      = Helper::generateDataForProxy($siteUserId, $configReq);

            $curl   = Curl::init();
            $result = $curl->request('post', $setSiteConfigUrl, $configReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('update site info failed');
            }
            $log->info(['return_results' => 'success']);
            return 'success';
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return 'fail';
        }
    }
}
