<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: core/user.proto

namespace Library\Plugin;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>core.SimpleUserProfile</code>
 */
class SimpleUserProfile extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string site_user_id = 1;</code>
     */
    private $site_user_id = '';
    /**
     * Generated from protobuf field <code>string user_name = 2;</code>
     */
    private $user_name = '';
    /**
     * Generated from protobuf field <code>string user_photo = 3;</code>
     */
    private $user_photo = '';
    /**
     * Generated from protobuf field <code>.core.UserStatus user_status = 4;</code>
     */
    private $user_status = 0;

    public function __construct()
    {
        \GPBMetadata\Core\User::initOnce();
        parent::__construct();
    }

    /**
     * Generated from protobuf field <code>string site_user_id = 1;</code>
     * @return string
     */
    public function getSiteUserId()
    {
        return $this->site_user_id;
    }

    /**
     * Generated from protobuf field <code>string site_user_id = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setSiteUserId($var)
    {
        GPBUtil::checkString($var, true);
        $this->site_user_id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string user_name = 2;</code>
     * @return string
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Generated from protobuf field <code>string user_name = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setUserName($var)
    {
        GPBUtil::checkString($var, true);
        $this->user_name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string user_photo = 3;</code>
     * @return string
     */
    public function getUserPhoto()
    {
        return $this->user_photo;
    }

    /**
     * Generated from protobuf field <code>string user_photo = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setUserPhoto($var)
    {
        GPBUtil::checkString($var, true);
        $this->user_photo = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.core.UserStatus user_status = 4;</code>
     * @return int
     */
    public function getUserStatus()
    {
        return $this->user_status;
    }

    /**
     * Generated from protobuf field <code>.core.UserStatus user_status = 4;</code>
     * @param int $var
     * @return $this
     */
    public function setUserStatus($var)
    {
        GPBUtil::checkEnum($var, \Library\Plugin\UserStatus::class);
        $this->user_status = $var;

        return $this;
    }
}
