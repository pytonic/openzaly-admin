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
use Library\Plugin\HaiUserSealUpRequest;
use Library\Plugin\HaiUserUpdateRequest;
use Library\Plugin\UserProfile;
use Library\Plugin\UserStatus;
use Library\Plugin\HaiUserSearchRequest;
use Library\Plugin\HaiUserSearchResponse;
use Library\Plugin\HaiUserProfileRequest;
use Library\Plugin\HaiUserProfileResponse;
use Library\Plugin\HaiUserListRequest;
use Library\Plugin\HaiUserListResponse;
use Zaly\Curl;
use Zaly\Log;
use Zaly\Config;
use Library\Helper;
use Library\SiteMemberLists;

class ManageUser
{
    /**
     * 成员管理-站点成员管理
     *
     * @author 尹少爷 2018.1.4
     *
     * @param proto $result
     *
     */
    public static function getSiteUsers($params, $getMembersUrl, $pageSize = 12)
    {
        $log    = Log::init();
        $logText = [
            'msg'    => 'get site members',
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $result = Helper::getDataFromProxy($params);
            $siteUserId = isset($result['site_user_id']) ? $result['site_user_id'] : '';
            $page       = isset($result['data']['page']) ? $result['data']['page'] : 1;
            $results    = self::getMembersList($siteUserId, $getMembersUrl, $page, $pageSize);
            $log->info(['return_results' => $results]);
            return $results;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return [];
        }
    }

    /**
     * 成员管理-站点成员管理-用户状态修改
     *
     * @author 尹少爷 2018.1.4
     *
     * @param proto $result
     *
     */
    public static function sealupSiteUser($params, $sealupSiteUserUrl)
    {
        $log    = Log::init();
        $logText = [
            'msg'    => 'update site members status',
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $result = Helper::getDataFromProxy($params);
            $sealupSiteUserId = isset($result['data']['site_user_id']) ? $result['data']['site_user_id'] : '';
            $siteUserId       = $result['site_user_id'];

            $sealupUserReq = new HaiUserSealUpRequest();
            if ($result['data']['type'] == 'unfreeze') {
                $status = UserStatus::NORMAL;
            } else {
                $status = UserStatus::SEALUP;
            }
            $log->info("update user status is ".$status);
            $sealupUserReq->setStatus($status);
            $sealupUserReq->setSiteUserId($sealupSiteUserId);
            $sealupUserReq = $sealupUserReq->serializeToString();
            $sealupUserReq = Helper::generateDataForProxy($siteUserId, $sealupUserReq);

            $curl   = Curl::init();
            $result = $curl->request('post', $sealupSiteUserUrl, $sealupUserReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('update user info failed');
            }
            $log->info(['return_results' => $results]);
            return  'success';
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return  'fail';
        }
    }
    /**
     * 成员管理-站点成员管理-查看站点用户信息
     *
     * @author 尹少爷 2018.1.11
     *
     * @param proto $result
     *
     */
    public static function getSiteUserInfo($params, $userInfoUrl)
    {
        $log = Log::init();
        $logText = [
            'msg'    => "get member info",
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $result     = Helper::getDataFromProxy($params);
            $userId     = isset( $result['data']['site_user_id']) ?  $result['data']['site_user_id'] : '';
            $siteUserId = $result['site_user_id'];
            $userProReq = new HaiUserProfileRequest();
            $userProReq->setSiteUserId($userId);
            $userProReq = $userProReq->serializeToString();
            $userProReq = Helper::generateDataForProxy($siteUserId, $userProReq);

            $curl   = Curl::init();
            $result = $curl->request('post', $userInfoUrl, $userProReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('get member info failed');
            }
            $data = $results['data'];

            $userProRep = new HaiUserProfileResponse();
            $userProRep->mergeFromString($data);
            $userInfo  = $userProRep->getUserProfile();
            $userInfos = [];
            $userInfos['user_id']     = $userInfo->getSiteUserId();
            $userInfos['user_name']   = $userInfo->getUserName();
            $userInfos['user_photo']  = $userInfo->getUserPhoto();
            $userInfos['user_desc']   = $userInfo->getSelfIntroduce();
            $userInfos['user_status'] = $userInfo->getUserStatus();
            $log->info(['return_results' => $userInfos]);
            return $userInfos;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return[];
        }
    }
    /**
     * 成员管理-站点成员管理-更新用户信息
     *
     * @author 尹少爷 2018.1.10
     *
     * @param proto $result
     *
     */
    public static function updateSiteUserInfo($params, $updateSiteUserUrl)
    {
        $log = Log::init();
        $logText = [
            'msg'    => "update member info",
            'method' => __METHOD__,
            'params' => $params,
        ];
        try {
            $log->info($logText);
            $result = Helper::getDataFromProxy($params);
            $userName        = isset($result['data']['user_name']) ? $result['data']['user_name'] : '';
            $siteUserId      = isset($result['site_user_id']) ? $result['site_user_id'] : '';
            $editSiteUserId  = isset($result['data']['site_user_id']) ? $result['data']['site_user_id'] : '';
            $userPhoto       = isset($result['data']['user_photo']) ? $result['data']['user_photo'] : '';

            $userProfile  = new UserProfile();
            $userProfile->setSiteUserId($editSiteUserId);
            $userProfile->setUserName($userName);
            $userProfile->setUserPhoto($userPhoto);
            $updateUserReq = new HaiUserUpdateRequest();
            $updateUserReq->setUserProfile($userProfile);
            $updateUserReq = $updateUserReq->serializeToString();
            $updateUserReq = Helper::generateDataForProxy($siteUserId, $updateUserReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $updateSiteUserUrl, $updateUserReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('update member info failed');
            }
            $log->info(['return_results' => 'success']);
            return "success";
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return  "fail";
        }
    }

    /**
     * 站点广场 获取用户数据
     *
     * @author 尹少爷 2017.12.27
     *
     * @param string siteUserId
     * @param string getMembersUrl
     * @param string page
     * @param string pageSize
     *
     * @return array
     */
    public static function getMembersList($siteUserId, $getMembersUrl = '', $page = 1, $pageSize = 20)
    {
        $log = Log::init();
        $logText = [
            'msg'          => "pull member info",
            'method'       => __METHOD__,
            'site_user_id' => $siteUserId,
        ];
        try {
            $log->info($logText);
            $reqMemberLists = new HaiUserListRequest();
            $reqMemberLists->setPageNumber($page);
            $reqMemberLists->setPageSize($pageSize);
            $msgPacked  = $reqMemberLists->serializeToString();
            $msgPacked  = Helper::generateDataForProxy($siteUserId, $msgPacked);

            $curl    = Curl::init();
            $result  = $curl->request('post', $getMembersUrl, $msgPacked);
            $results = Helper::getDataFromPlugin($result);

            $output   = [];
            $loading  = true;
            if ($results['error'] !== 'success') {
                $output = ["data" => $output, "loading" => $loading];
                return $output;
            }
            $data      = $results['data'];
            $response  = new HaiUserListResponse();
            $response->mergeFromString($data);
            $userInfos = $response->getUserProfile();
            if ($userInfos) {
                foreach ($userInfos as $key => $userInfo) {
                    $output[$key]['site_user_id']     = $userInfo->getSiteUserId();
                    $output[$key]['site_user_name']   = $userInfo->getUserName();
                    $output[$key]['site_user_photo']  = $userInfo->getUserPhoto();
                    $output[$key]['site_user_status'] = $userInfo->getUserStatus();
                }
            }
            if (count($output) >= 12) {
                $loading = false;
            }
            $output = ["data" => $output, "loading" => $loading];
            return $output;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return ["data" => [], "loading" => false];
        }
    }
}
