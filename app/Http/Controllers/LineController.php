<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot\Constant\Flex\ComponentButtonStyle;
use LINE\LINEBot\Constant\Flex\ComponentLayout;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\PostbackEvent;
use LINE\LINEBot\Event\UnfollowEvent;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\BoxComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ButtonComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ImageComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\TextComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\BubbleContainerBuilder;
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

class LineController extends Controller
{
    /**
     * Property bot.
     *
     * @var  \LINE\LINEBot
     */
    protected $bot;

    public function __construct()
    {
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(config('linebot.channel.access_token'));
        $this->bot  = new \LINE\LINEBot($httpClient, ['channelSecret' => config('linebot.channel.secret')]);
    }

    public function webhook(Request $request)
    {
        $bot = $this->bot;

        $signature = $request->header(\LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE);
        $body      = $request->getContent();

        try {
            $events = $bot->parseEventRequest($body, $signature);
        } catch (\Exception $e) {
            return response()->json([
                'state'   => false,
                'message' => $e->getMessage(),
            ]);
        }

        foreach ($events as $event) {
            $line_user_id = $event->getUserId();

            if ($event instanceof FollowEvent) {
                $user = new User;

                $user->uid = $line_user_id;

                $user->save();
            } elseif ($event instanceof UnfollowEvent) {
                $users = User::query()->where('uid', $line_user_id);

                foreach ($users as $user) {
                    try{
                        $user->events->delete();

                        $user->delete();
                    } catch (\Exception $e) {
                        $bot->replyMessage($event->getReplyToken(), new TextMessageBuilder('系統發生錯誤'));
                    }
                }
            } elseif ($event instanceof MessageEvent) {
                $message_type = $event->getMessageType();

                $user = User::query()
                    ->where('uid', $line_user_id)
                    ->first();

                if (!$user) {
                    $text_message = new TextMessageBuilder('發生錯誤');

                    $bot->replyMessage($event->getReplyToken(), $text_message);
                }

                switch ($message_type) {
                    case 'text':
                        $text = $event->getText();

                        switch ($text) {
                            case '待辦清單':
                                $user_events = $user->events;

                                $carousels = [];

                                foreach ($user_events as $user_event) {
                                    $column = new CarouselColumnTemplateBuilder(null, $user_event->event, null, [
                                        new PostbackTemplateActionBuilder('編輯', json_encode([
                                            'action'   => 'edit',
                                            'event_id' => $user_event->id,
                                        ])),
                                        new PostbackTemplateActionBuilder('完成', json_encode([
                                            'action'   => 'done',
                                            'event_id' => $user_event->id,
                                        ])),
                                    ]);

                                    $carousels[] = $column;
                                }

                                $carousel_message = new CarouselTemplateBuilder($carousels);

                                $text_message = new TemplateMessageBuilder('待辦清單', $carousel_message);

                                $bot->replyMessage($event->getReplyToken(), $text_message);

                                break;

                            default:
                                $text_message = new TextMessageBuilder($text);

                                $bot->replyMessage($event->getReplyToken(), $text_message);

                                break;
                        }

                        break;
                }
            } elseif ($event instanceof PostbackEvent) {
                $postback_data = json_decode($event->getPostbackData());

                $action   = $postback_data->action;
                $event_id = $postback_data->event_id;

                $user_event = Event::query()->find($event_id);

                switch ($action) {
                    case 'done':
                    {
                        try {
                            $title = $user_event->event;
                            $user_event->delete();

                            $bot->replyMessage($event->getReplyToken(), new TextMessageBuilder($title . ' 待辦事項已完成'));
                        } catch (\Exception $e) {
                            $bot->replyMessage($event->getReplyToken(), new TextMessageBuilder('系統發生錯誤'));
                        }
                    }
                }

                $bot->replyMessage($event->getReplyToken(), new TextMessageBuilder(json_encode($postback_data)));
            } else {
                $text_message = new TextMessageBuilder('不要亂玩...');

                $bot->replyMessage($event->getReplyToken(), $text_message);
            }
        }

        return response()->json([
            'state'   => true,
            'message' => $text_message,
        ]);
    }
}
