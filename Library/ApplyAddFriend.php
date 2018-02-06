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

use Library\Plugin\HaiFriendApplyRequest;
use Library\Helper;
use Zaly\Curl;
use Zaly\Log;

class ApplyAddFriend
{
    /**
     * 申请添加好友
     *
     * @author 尹少爷 2017.12.27
     *
     * @param proto $result
     *
     * @param string $applyFriendUrl
     */
    public static function handleApplyAddFriendRequest($params, $applyFriendUrl)
    {
        $log = Log::init();

        try {
            $log->info('申请添加好友');
            $result     = Helper::getDataFromProxy($params);
            $log->info($result);
            $siteUserId = $result['site_user_id'];
            $friendId   = $result['data']['site_user_id'];
            $reason     = $result['data']['apply_reason'];
            $log->info([$siteUserId, $friendId, $reason]);
            return self::sendApplyFriendRequest($siteUserId, $friendId, $reason, $applyFriendUrl);
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
        }
    }

    /**
     * 发送申请添加好友
     *
     * @author 尹少爷 2017.12.27
     *
     * @param string $siteUserId 发起人id
     * @param string $friendId  接收好友请求的用户id
     * @param string $reason  添加说明
     * @param string $applyFriendUrl  处理添加好友申请功能的url
     *
     * @return string
     */
    public static function sendApplyFriendRequest($siteUserId, $friendId, $reason, $applyFriendUrl = '')
    {
        $log = Log::init();
        try {
            $log->info('发送申请添加好友');
            $friendApplyRequest  = new HaiFriendApplyRequest();
            $friendApplyRequest->setSiteFriendId($friendId);
            $friendApplyRequest->setApplyReason($reason);
            $msgPacked  = $friendApplyRequest->serializeToString();
            $msgPacked = Helper::generateDataForProxy($siteUserId, $msgPacked);

            $curl    = Curl::init();
            $result  = $curl->request('post', $applyFriendUrl, $msgPacked);
            $params  = Helper::getDataFromPlugin($result);
            $log->info($params);
            if ($params['error'] == 'error') {
                throw new \Exception('添加失败');
            }
            return "success";
        } catch (\Exception $ex) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return "fail";
        }
    }
}
