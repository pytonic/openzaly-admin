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

use Library\Plugin\HaiGroupListRequest;
use Library\Plugin\HaiGroupListResponse;
use Library\Plugin\HaiGroupUpdateProfileRequest;
use Library\Plugin\HaiGroupProfileRequest;
use Library\Plugin\HaiGroupProfileResponse;
use Library\Plugin\HaiGroupMembersResponse;
use Library\Plugin\HaiGroupDeleteRequest;
use Library\Plugin\HaiGroupRemoveMemberRequest;
use Library\Plugin\HaiGroupMembersRequest;
use Library\Plugin\HaiGroupAddMemberRequest;
use Library\Plugin\HaiGroupNonmembersRequest;
use Library\Plugin\HaiGroupNonmembersResponse;
use Library\Plugin\GroupProfile;
use Library\ManageUser;
use Zaly\Curl;
use Zaly\Log;
use Zaly\Config;
use Library\Helper;

class ManageGroup
{
    /**
     * 群管理-获取站点下群组列表
     *
     * @author 尹少爷 2018.1.4
     *
     * @param proto $result
     *
     */
    public static function getGroupLists($params, $groupListUrl, $pageSize = 12)
    {
        $log    = Log::init();
        $loading = true;
        try {
            $log->info('获取站点群组列表');
            $result = Helper::getDataFromProxy($params);
            $log->info($result);
            $page = isset($result['data']['page']) ? $result['data']['page'] : 1;
            $siteUserId = $result['site_user_id'];
            $groupListReq = new HaiGroupListRequest();
            $groupListReq->setPageNumber($page);
            $groupListReq->setPageSize($pageSize);
            $groupListReq = $groupListReq->serializeToString();
            $groupListReq = Helper::generateDataForProxy($siteUserId, $groupListReq);
            $curl =  Curl::init();
            $result = $curl->request('post', $groupListUrl, $groupListReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('获取群组列表失败');
            }
            $data = $results['data'];
            $groupListRep = new HaiGroupListResponse();
            $groupListRep->mergeFromString($data);
            $groupLists = $groupListRep->getGroupProfile();
            $lists = [];
            foreach ($groupLists as $key => $group) {
                $lists[$key]['group_id'] = $group->getGroupId();
                $lists[$key]['group_name'] = $group->getGroupName();
                $lists[$key]['group_icon'] = $group->getGroupIcon();
            }
            if (count($lists) >= 12) {
                $loading = false;
            }
            $output = ['results' => $lists, 'loading' => $loading];
            $log->info($output);
            return $output;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return ['results' => [], 'loading' => $loading];
        }
    }
    /**
     * 群管理-获取站点下单个群组的群成员
     *
     * @author 尹少爷 2018.1.4
     *
     * @param proto $result
     *
     */
    public static function getGroupMembers($params, $groupMembersUrl, $pageSize = 30)
    {
        $log    = Log::init();
        $loading = true;
        try {
            $log->info('获取群下面的成员');
            $result = Helper::getDataFromProxy($params);
            $log->info($result);

            $siteUserId = isset($result['site_user_id']) ? $result['site_user_id'] : '';
            $groupId    = isset($result['data']['group_id']) ? $result['data']['group_id'] : '';
            $page       = isset($result['data']['page']) ? $result['data']['page'] : 1;

            $groupMemberReq = new HaiGroupMembersRequest();
            $groupMemberReq->setGroupId($groupId);
            $groupMemberReq->setPageNumber($page);
            $groupMemberReq->setPageSize($pageSize);
            $groupMemberReq = $groupMemberReq->serializeToString();
            $groupMemberReq = Helper::generateDataForProxy($siteUserId, $groupMemberReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $groupMembersUrl, $groupMemberReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('获取群成员失败');
            }
            $data = $results['data'];
            $groupMembersRep = new HaiGroupMembersResponse();
            $groupMembersRep->mergeFromString($data);
            $membersLists = $groupMembersRep->getGroupMember();

            $lists = [];
            foreach ($membersLists as $key => $member) {
                $memberInfo = $member->getProfile();
                $lists[$key]['site_user_id'] = $memberInfo->getSiteUserId();
                $lists[$key]['user_photo']   = $memberInfo->getUserPhoto();
                $lists[$key]['user_desc']    = $memberInfo->getSelfIntroduce();
                $lists[$key]['user_name']    = $memberInfo->getUserName();
                $lists[$key]['group_id']     = $groupId;
            }
            if (count($lists)>=12) {
                $loading = false;
            }
            $output = ['results' => $lists, 'loading' => $loading, 'group_id' => $groupId];
            $log->info($output);
            return $output;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return ['results' => [], 'loading' => $loading, 'group_id' => ''];
        }
    }

    /**
     * 群管理-获取站点下单个群组的群成员
     *
     * @author 尹少爷 2018.1.4
     *
     * @param proto $result
     *
     */
    public static function getGroupId($params)
    {
        $log    = Log::init();
        $loading = true;
        try {
            $log->info('获取群id');
            $result = Helper::getDataFromProxy($params);
            $log->info($result);
            $siteUserId = $result['site_user_id'];
            $groupId    = $result['data']['group_id'];
            $groupName  = $result['data']['group_name'];
            return ['group_id' => $groupId, 'group_name' => $groupName];
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return ['group_id' => ''];
        }
    }
    /**
     * 群管理-删除群成员
     *
     * @author 尹少爷 2018.1.4
     *
     * @param proto $result
     *
     */
    public static function removeGroupMember($params, $removeGroupUserUrl)
    {
        $log    = Log::init();
        try {
            $log->info('删除群成员');
            $result = Helper::getDataFromProxy($params);
            $log->info($result);
            $siteUserId = isset($result['site_user_id']) ? $result['site_user_id'] : '';
            $removeSiteUserId = $result['data']['remove_site_user_id'];
            $groupId = $result['data']['group_id'];
            $removeMemberReq = new HaiGroupRemoveMemberRequest();
            $removeMemberReq->setGroupId($groupId);
            $removeMemberReq->setGroupMember($removeSiteUserId);
            $removeMemberReq = $removeMemberReq->serializeToString();
            $removeMemberReq = Helper::generateDataForProxy($siteUserId, $removeMemberReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $removeGroupUserUrl, $removeMemberReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('删除群成员失败');
            }
            return "success";
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return "fail";
        }
    }
    /**
     * 群管理-添加群成员
     *
     * @author 尹少爷 2018.1.14
     *
     * @param proto $result
     *
     */
    public static function addGroupUser($params, $addUserToGroupUrl)
    {
        $log    = Log::init();
        try {
            $log->info('添加群成员');
            $result = Helper::getDataFromProxy($params);
            $log->info($result);
            $groupId = $result['data']['group_id'];
            $addSiteUserId = $result['data']['add_site_user_id'];
            $siteUserId    = $result['site_user_id'];
            $addMemberReq  = new HaiGroupAddMemberRequest();
            $addMemberReq->setGroupId($groupId);
            $addMemberReq->setGroupMember($addSiteUserId);
            $addMemberReq = $addMemberReq->serializeToString();
            $addMemberReq = Helper::generateDataForProxy($siteUserId, $addMemberReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $addUserToGroupUrl, $addMemberReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('删除群成员失败');
            }
            return "success";
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return 'fail';
        }
    }

    /**
     * 群管理-解散群
     *
     * @author 尹少爷 2018.1.5
     *
     * @param proto $result
     *
     */
    public static function disbandGroup($params, $disbandGroupUrl)
    {
        $log    = Log::init();
        try {
            $log->info('解散群');
            $log->info($disbandGroupUrl);
            $result = Helper::getDataFromProxy($params);
            $siteUserId = $result['site_user_id'];
            $groupId    = $result['data']['group_id'];

            $disbandGroupReq = new HaiGroupDeleteRequest();
            $disbandGroupReq->setGroupId($groupId);
            $disbandGroupReq = $disbandGroupReq->serializeToString();
            $disbandGroupReq = Helper::generateDataForProxy($siteUserId, $disbandGroupReq);
            $log->info([$result, $disbandGroupUrl]);

            $curl    = Curl::init();
            $result  = $curl->request('post', $disbandGroupUrl, $disbandGroupReq);
            $results = Helper::getDataFromPlugin($result);
            $log->info('获取解散结果');
            $log->info($results);
            if ($results['error'] == 'fail') {
                throw new \Exception('解散群失败');
            }
            return "success";
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return 'fail';
        }
    }
    /**
     * 群管理-得到群信息【名称 头像 公告】
     *
     * @author 尹少爷 2018.1.13
     *
     * @param proto $result
     *
     */
    public static function getGroupInfo($params, $groupInfoUrl)
    {
        $log = Log::init();
        try {
            $log->info('得到群信息');
            $result = Helper::getDataFromProxy($params);
            $log->info($result);
            $siteUserId = $result['site_user_id'];
            $groupId    = isset($result['data']['group_id']) ?$result['data']['group_id'] : '' ;

            $groupProfileReq = new HaiGroupProfileRequest();
            $groupProfileReq->setGroupId($groupId);
            $groupProfileReq = $groupProfileReq->serializeToString();
            $groupProfileReq = Helper::generateDataForProxy($siteUserId, $groupProfileReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $groupInfoUrl, $groupProfileReq);
            $results = Helper::getDataFromPlugin($result);
            $log->info('得到群信息结果');
            $log->info($results);
            if ($results['error'] == 'fail') {
                throw new \Exception('得到群信息失败');
            }
            $data = $results['data'];
            $groupProfileRep = new HaiGroupProfileResponse();
            $groupProfileRep->mergeFromString($data);
            $groupProfile = $groupProfileRep->getProfile();
            $list = [
                    'group_name'   => $groupProfile->getName(),
                    'group_id'     => $groupProfile->getId(),
                    'group_icon'   => $groupProfile->getIcon(),
                    'group_notice' => $groupProfile->getGroupNotice(),
            ];
            $log->info($list);
            return $list;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return [];
        }
    }
    /**
     * 群管理-修改群信息
     *
     * @author 尹少爷 2018.1.13
     *
     * @param proto $result
     *
     */
    public static function setGroupInfo($params, $updateGroupInfoUrl)
    {
        $log    = Log::init();
        try {
            $log->info('修改群信息');
            $log->info($params);
            $result = Helper::getDataFromProxy($params);
            $log->info($result);

            $siteUserId  = $result['site_user_id'];
            $groupName   = $result['data']['group_name'];
            $groupId     = $result['data']['group_id'];
            $groupIcon   = $result['data']['group_icon'];
            $groupNotice = isset($result['data']['group_notice']) ? $result['data']['group_notice'] : '';

            $groupProfile = new GroupProfile();
            $groupProfile->setId($groupId);
            $groupProfile->setName($groupName);
            $groupProfile->setGroupNotice($groupNotice);
            $groupProfile->setIcon($groupIcon);
            $groupUpdateReq = new HaiGroupUpdateProfileRequest();
            $groupUpdateReq->setProfile($groupProfile);
            $groupUpdateReq = $groupUpdateReq->serializeToString();
            $groupUpdateReq = Helper::generateDataForProxy($siteUserId, $groupUpdateReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $updateGroupInfoUrl, $groupUpdateReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('修改群信息');
            }
            return 'success';
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return 'fail';
        }
    }
    /**
     * 群管理-获取不在群中的站点用户
     *
     * @author 尹少爷 2018.1.14
     *
     * @param Array $result
     *
     */
    public static function getSiteUsers($params, $getMembersUrl, $pageSize)
    {
        $log = Log::init();
        try {
            $loading = true;
            $log->info('获取不在群中的站点用户');
            $result = Helper::getDataFromProxy($params);
            $groupId    = $result['data']['group_id'];
            $siteUserId = $result['site_user_id'];
            $page = isset($result['data']['page']) ? $result['data']['page'] : 1;

            $nonmemberReq = new HaiGroupNonmembersRequest();
            $nonmemberReq->setGroupId($groupId);
            $nonmemberReq->setPageSize($pageSize);
            $nonmemberReq->setPageNumber($page);
            $nonmemberReq = $nonmemberReq->serializeToString();
            $nonmemberReq = Helper::generateDataForProxy($siteUserId, $nonmemberReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $getMembersUrl, $nonmemberReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('获取不在群的站点用户失败');
            }
            $data = $results['data'];
            $nonmemberRep = new HaiGroupNonmembersResponse();
            $nonmemberRep->mergeFromString($data);
            $lists = $nonmemberRep->getGroupMember();
            foreach ($lists as $key => $val) {
                $userProfile = $val->getProfile();
                $output[$key]['site_user_id']     = $userProfile->getSiteUserId();
                $output[$key]['site_user_name']   = $userProfile->getUserName();
                $output[$key]['site_user_photo']  = $userProfile->getUserPhoto();
                $output[$key]['site_user_status'] = $userProfile->getUserStatus();
            }
            if (count($output) >= 12) {
                $loading = false;
            }
            $log->info($output);
            return ["data" => $output, "loading" => $loading, 'group_id' => $groupId];
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return ['results' => [], 'loading' => true, 'group_id' => ''];
        }
    }
}
