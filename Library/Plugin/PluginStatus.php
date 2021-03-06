<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: core/plugin.proto

namespace Library\Plugin;

/**
 * Protobuf enum <code>Core\PluginStatus</code>
 */
class PluginStatus
{
    /**
     *默认禁用状态
     *
     * Generated from protobuf enum <code>DISABLED = 0;</code>
     */
    const DISABLED = 0;
    /**
     *首页扩展
     *
     * Generated from protobuf enum <code>AVAILABLE_HOME_PAGE = 1;</code>
     */
    const AVAILABLE_HOME_PAGE = 1;
    /**
     *首页只对管理员可见
     *
     * Generated from protobuf enum <code>ADMIN_HOME_PAGE_SEE = 2;</code>
     */
    const ADMIN_HOME_PAGE_SEE = 2;
    /**
     *消息帧扩展
     *
     * Generated from protobuf enum <code>AVAILABLE_MSG_PAGE = 3;</code>
     */
    const AVAILABLE_MSG_PAGE = 3;
    /**
     *消息帧只对管理员可见
     *
     * Generated from protobuf enum <code>ADMIN_MSG_PAGE_SEE = 4;</code>
     */
    const ADMIN_MSG_PAGE_SEE = 4;
    /**
     *此状态下，可以展示所有上述状态
     *
     * Generated from protobuf enum <code>ALL_PLUGIN = 100;</code>
     */
    const ALL_PLUGIN = 100;
}
