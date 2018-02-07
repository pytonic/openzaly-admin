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

use Library\Plugin\HaiUicListRequest;
use Library\Plugin\HaiUicListResponse;
use Library\Plugin\HaiUicCreateRequest;
use Library\Plugin\UicStatus;
use Zaly\Curl;
use Zaly\Log;
use Library\Helper;

class ManageInviteCode
{

    /**
     * 邀请码-获取邀请码列表
     *
     * @author 尹少爷 2018.1.4
     *
     * @param proto $result
     *
     */
    public static function getVerifyCodeLists($params, $getUicUrl, $pageSize = 12)
    {
        $log    = Log::init();
        $loading = true;
        try {
            $log->info('获取邀请码参数');
            $result = Helper::getDataFromProxy($params);
            $log->info($result);
            $page       = isset($result['data']['page']) ? $result['data']['page'] : 1;
            $siteUserId = isset($result['site_user_id']) ? $result['site_user_id'] : '';
            $statusKey  = isset($result['data']['code_status']) ? $result['data']['code_status'] : 'used';
            $log->info([['page' => $page, 'page_size' => $pageSize, 'status' => $statusKey]]);

            $uicReq = new HaiUicListRequest();
            $uicReq->setPageNumber($page);
            $uicReq->setPageSize($pageSize);
            $status = constant(UicStatus::class."::".strtoupper($statusKey));
            $uicReq->setStatus($status);
            $uicReq = $uicReq->serializeToString();
            $uicReq = Helper::generateDataForProxy($siteUserId, $uicReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $getUicUrl, $uicReq);
            $results = Helper::getDataFromPlugin($result);
            if ($results['error'] == 'fail') {
                throw new \Exception('获取邀请码列表失败');
            }
            $data   = $results['data'];
            $uicRep = new HaiUicListResponse();
            $uicRep->mergeFromString($data);
            $uidLists = $uicRep->getUicInfo();
            $lists = [];
            foreach ($uidLists as $key => $uic) {
                $lists[$key]['code'] = $uic->getUic();
                $lists[$key]['use_site_user_name'] = $uic->getUserName();
                $lists[$key]['code_status'] = $uic->getStatus();
            }
            if (count($lists) >= 12) {
                $loading = false;
            }
            $output = ['results' => $lists, 'loading' =>$loading];
            $log->info("获取结果");
            $log->info($output);
            return $output;
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return ['results' => [], 'loading' =>$loading];
        }
    }
    /**
     * 邀请码-生成新的邀请码列表
     *
     * @author 尹少爷 2018.1.4
     *
     * @param proto $result
     *
     */
    public static function generateVerifyCodes($params, $generateVerifyUrl, $uicNumber = 20)
    {
        $log    = Log::init();
        try {
            $log->info('生成邀请码列表');
            $results = Helper::getDataFromProxy($params);
            $log->info($results);
            $siteUserId   = $results['site_user_id'];
            $uicCreateReq = new HaiUicCreateRequest();
            $uicCreateReq->setUicNumber($uicNumber);
            $uicCreateReq = $uicCreateReq->serializeToString();
            $uicCreateReq = Helper::generateDataForProxy($siteUserId, $uicCreateReq);

            $curl    = Curl::init();
            $result  = $curl->request('post', $generateVerifyUrl, $uicCreateReq);
            $results = Helper::getDataFromPlugin($result);
            $log->info("获取结果");
            $log->info($results);
            if ($results['error'] == 'fail') {
                throw new \Exception('生成邀请码失败');
            }
            return 'success';
        } catch (\Exception $e) {
            $message = sprintf("msg:%s file:%s:%d", $e->getMessage(), $e->getFile(), $e->getLine());
            $log->error($message);
            return 'fail';
        }
    }
}
