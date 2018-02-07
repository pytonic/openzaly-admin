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

use Library\Plugin\HaiUserRelationListRequest;
use Library\Plugin\HaiUserRelationListResponse;
use Library\Helper;
use Zaly\Curl;
use Zaly\Log;

class SiteMemberLists
{

    public static function getListsByProxy($params, $getMembersUrl, $pageSize = 12)
    {
        $log = Log::init();
        try {
            $params = Helper::getDataFromProxy($params);
            $siteUserId = $params['site_user_id'];
            $page = $params['data']['page'];
            return self::getMembersFromSite($siteUserId, $getMembersUrl, $page, $pageSize);
        } catch (\Exception $ex) {
            $message = sprintf("msg:%s file:%s:%d", $ex->getMessage(), $ex->getFile(), $ex->getLine());
            $log->error($message);
            return ["data" => [], "loading" => false];
        }
    }
    /**
     * 站点广场 获取用户数据
     *
     * @author 尹少爷 2017.12.27
     *
     * @param string siteUserId 站点用户请求id
     * @param string getMembersUrl 站点地址
     * @param string page 站点地址
     * @param string pageSize 站点地址
     *
     * @return array
     */
    public static function getMembersFromSite($siteUserId, $getMembersUrl = '', $page = 1, $pageSize = 20)
    {
        $log = Log::init();
        try {
            $log->info('getMembersFromSite');
            return self::getMembersList($siteUserId, $getMembersUrl, $page, $pageSize);
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return ["data" => [], "loading" => false];
        }
    }
    /**
     * 站点广场 获取用户数据
     *
     * @author 尹少爷 2017.12.27
     *
     * @param string siteUserId 站点用户请求id
     * @param string getMembersUrl 站点地址
     * @param string page 站点地址
     * @param string pageSize 站点地址
     *
     * @return array
     */
    public static function getMembersList($siteUserId, $getMembersUrl = '', $page = 1, $pageSize = 20)
    {
        $log = Log::init();
        try {
            $log->info('获取用户数据');
            $log->info(['siteUserId'=>$siteUserId]);
            $reqMemberLists = new HaiUserRelationListRequest();
            $reqMemberLists->setPageNumber($page);
            $reqMemberLists->setPageSize($pageSize);
            $msgPacked  = $reqMemberLists->serializeToString();
            $msgPacked  = Helper::generateDataForProxy($siteUserId, $msgPacked);

            $curl   = Curl::init();
            $result = $curl->request('post', $getMembersUrl, $msgPacked);
            $results  = Helper::getDataFromPlugin($result);
            $log->info('获取用户数据请求结果');
            $log->info($results);

            $output  = [];
            $loading = true;
            if ($results['error'] !== 'success') {
                return ["data" => $output, "loading" => $loading,'current_site_user_id' => ''];
            }
            $data = $results['data'];
            $response  = new HaiUserRelationListResponse();
            $response->mergeFromString($data);
            $userInfos = $response->getUserProfile();

            if ($userInfos) {
                foreach ($userInfos as $key => $userInfo) {
                    $output[$key]['site_user_relation'] = $userInfo->getRelation();
                    $userInfo = $userInfo->getProfile();
                    $output[$key]['site_user_id']    = $userInfo->getSiteUserId();
                    $output[$key]['site_user_name']  = $userInfo->getUserName();
                    $output[$key]['site_user_photo'] = $userInfo->getUserPhoto();
                }
            }
            if (count($output) >= 12) {
                $loading = false;
            }
            $output = ["data" => $output, "loading" => $loading, 'current_site_user_id' => $siteUserId];
            $log->info('获取用户数据');
            $log->info($output);
            return $output;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return ["data" => [], "loading" => false, 'current_site_user_id' => ''];
        }
    }
}
