<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: livekit_webhook.proto

namespace GPBMetadata;

class LivekitWebhook
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\LivekitModels::initOnce();
        \GPBMetadata\LivekitEgress::initOnce();
        \GPBMetadata\LivekitIngress::initOnce();
        $pool->internalAddGeneratedFile(
            '
�
livekit_webhook.protolivekitlivekit_egress.protolivekit_ingress.proto"�
WebhookEvent
event (	
room (2
participant (2.livekit.ParticipantInfo(
egress_info	 (2.livekit.EgressInfo*
ingress_info
 (2.livekit.IngressInfo!
track (2.livekit.TrackInfo

id (	

created_at (BFZ#github.com/livekit/protocol/livekit�
        , true);

        static::$is_initialized = true;
    }
}
