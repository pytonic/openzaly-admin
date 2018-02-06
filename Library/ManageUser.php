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
        try {
            $log->info('获取站点成员列表');
            $result = Helper::getDataFromProxy($params);
            $log->info($result);
            $siteUserId = isset($result['site_user_id']) ? $result['site_user_id'] : '';
            $page       = isset($result['data']['page']) ? $result['data']['page'] : 1;
            $results    = self::getMembersList($siteUserId, $getMembersUrl, $page, $pageSize);
            $log->info($results);
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
        try {
            $log->info('用户状态修改');
            $result = Helper::getDataFromProxy($params);
            $log->info([$result, $sealupSiteUserUrl]);
            $sealupSiteUserId = isset($result['data']['site_user_id']) ? $result['data']['site_user_id'] : '';
            $siteUserId       = $result['site_user_id'];

            $sealupUserReq = new HaiUserSealUpRequest();
            if ($result['data']['type'] == 'unfreeze') {
                $status = UserStatus::NORMAL;
            } else {
                $status = UserStatus::SEALUP;
            }
            $log->info("用户状态修改为".$status);
            $sealupUserReq->setStatus($status);
            $sealupUserReq->setSiteUserId($sealupSiteUserId);
            $sealupUserReq = $sealupUserReq->serializeToString();
            $sealupUserReq = Helper::generateDataForProxy($siteUserId, $sealupUserReq);

            $curl   = Curl::init();
            $result = $curl->request('post', $sealupSiteUserUrl, $sealupUserReq);
            $log->info('获取用户状态修改结果');
            $log->info($result);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('用户状态修改失败');
            }
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
        try {
            $log->info($params);
            $result = Helper::getDataFromProxy($params);
            $log->info('获取用户信息');
            $log->info([$result, $userInfoUrl]);
            $userId     = isset( $result['data']['site_user_id']) ?  $result['data']['site_user_id'] : '';
            $siteUserId = $result['site_user_id'];

            $userProReq = new HaiUserProfileRequest();
            $userProReq->setSiteUserId($userId);
            $userProReq = $userProReq->serializeToString();
            $userProReq = Helper::generateDataForProxy($siteUserId, $userProReq);

            $curl   = Curl::init();
            $result = $curl->request('post', $userInfoUrl, $userProReq);
            $log->info('获取用户信息');
            $log->info($result);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('获取用户信息失败');
            }
            $data = $results['data'];

            $userProRep = new HaiUserProfileResponse();
            $userProRep->mergeFromString($data);
            $userInfo  = $userProRep->getUserProfile();
            $log->info('获取用户信息-用户状态');
            $log->info($userInfo->getUserStatus());
            $userInfos = [];
            $userInfos['user_id']     = $userInfo->getSiteUserId();
            $userInfos['user_name']   = $userInfo->getUserName();
            $userInfos['user_photo']  = $userInfo->getUserPhoto();
            $userInfos['user_desc']   = $userInfo->getSelfIntroduce();
            $userInfos['user_status'] = $userInfo->getUserStatus();
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
        try {
            $result = Helper::getDataFromProxy($params);
            $log->info('更新用户信息源');
            $log->info([$result, $updateSiteUserUrl]);
            $userName        = isset($result['data']['user_name']) ? $result['data']['user_name'] : '';
            $siteUserId      = isset($result['site_user_id']) ? $result['site_user_id'] : '';
            $editSiteUserId  = isset($result['data']['site_user_id']) ? $result['data']['site_user_id'] : '';
            $userPhoto       = isset($result['data']['user_photo']) ? $result['data']['user_photo'] : '';

            $userProfile = new UserProfile();
            $userProfile->setSiteUserId($editSiteUserId);
            $userProfile->setUserName($userName);
            $userProfile->setUserPhoto($userPhoto);
            $updateUserReq = new HaiUserUpdateRequest();
            $updateUserReq->setUserProfile($userProfile);
            $updateUserReq = $updateUserReq->serializeToString();
            $updateUserReq = Helper::generateDataForProxy($siteUserId, $updateUserReq);

            $curl   = Curl::init();
            $result = $curl->request('post', $updateSiteUserUrl, $updateUserReq);
            $log->info('获取更新结果');
            $log->info($result);
            if ($result == 'error') {
                throw new \Exception('更新失败');
            }
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('更新失败');
            }
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
            $log->info(['siteUserId'=>$siteUserId]);
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
                return ["data" => $output, "loading" => $loading];
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
            return ["data" => $output, "loading" => $loading];
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return ["data" => [], "loading" => false];
        }
    }
}
