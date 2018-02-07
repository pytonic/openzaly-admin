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
namespace Controller;

use Controller\BaseController;

use Zaly\Request;
use Zaly\Curl;
use Zaly\Config;
use Zaly\Log;
use Library\SiteMemberLists;
use Library\ApplyAddFriend;

class SiteMemberController extends BaseController
{
    public $request;
    public function __construct()
    {
        $this->request = Request::init();
        $this->log     = Log::init();
        $this->config  = Config::init();
    }
    /**
     * 站点广场 获取用户数据
     *
     * @author 尹少爷 2017.12.23
     *
     */
    public function membersAction()
    {
        $siteUserId = $this->request->get('siteUserId');
        $results    = $this->getMembersFromSite($siteUserId);
        $results['site_user_id'] = $siteUserId;
        echo $this->render('siteMember/siteMemberList', $results);
    }
    /**
     * 站点广场 下拉获取用户数据
     *
     * @author 尹少爷 2017.12.23
     *
     */
    protected function getMembersFromSite($siteUserId, $page = 1)
    {
        $getMembersUrl = $this->config['base']['member_relation_list_site'];
        $pageSize = $this->config['base']['page_size'];
        return  SiteMemberLists::getMembersFromSite($siteUserId, $getMembersUrl, $page, $pageSize);
    }

    /**
     * 发送申请添加好友
     *
     * @author 尹少爷 2017.12.23
     */
    public function applyAddFriendAction()
    {
        $params  = file_get_contents("php://input");
        $applyFriendUrl  = $this->config['base']['apply_friend_url'];
        $result = ApplyAddFriend::handleApplyAddFriendRequest($params, $applyFriendUrl);
        echo  $result;
    }

    /**
     * 拉取站点用户列表
     *
     * @author 尹少爷 2017.12.23
     */
    public function pullMemberListAction()
    {
        try {
            $params  = file_get_contents("php://input");
            $getMembersUrl = $this->config['base']['member_relation_list_site'];
            $pageSize = $this->config['base']['page_size'];
            $results  = SiteMemberLists::getListsByProxy($params, $getMembersUrl, $pageSize);
            echo json_encode($results, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $ex) {
            echo [];
        }
    }
}
