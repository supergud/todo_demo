<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\PostbackEvent;
use LINE\LINEBot\Event\UnfollowEvent;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;

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
                $users = User::query()
                    ->where('uid', $line_user_id)
                    ->get();

                Log::info(json_encode($users));

                foreach ($users as $user) {
                    $user->delete();
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
                        $text = explode('@', $event->getText());

                        $action = $text[0];
                        $data   = json_decode($text[1] ?? '{}');

                        switch ($action) {
                            case '待辦清單':
                                $user_events = $user->events;

                                if ($user_events->count()) {
                                    $carousels = [];

                                    foreach ($user_events as $user_event) {
                                        $column = new CarouselColumnTemplateBuilder(null, $user_event->event, null, [
                                            new UriTemplateActionBuilder('編輯', 'line://app/1627654560-O93NjxGV?event_id=' . $user_event->id),
                                            new PostbackTemplateActionBuilder('完成', json_encode([
                                                'action'   => 'done',
                                                'event_id' => $user_event->id,
                                            ])),
                                        ]);

                                        $carousels[] = $column;
                                    }

                                    $carousel_message = new CarouselTemplateBuilder($carousels);

                                    $text_message = new TemplateMessageBuilder('待辦清單', $carousel_message);
                                } else {
                                    $text_message = new TextMessageBuilder('目前沒有待辦事項');
                                }

                                $bot->replyMessage($event->getReplyToken(), $text_message);

                                break;

                            case '新增':
                                $new_event = new Event;

                                $new_event->event    = $data->event;
                                $new_event->deadline = $data->deadline ?: null;
                                $new_event->user_id  = $user->id;

                                $new_event->save();

                                $text_message = new TextMessageBuilder("新增待辦事項成功：\n" . $new_event->event);

                                $bot->replyMessage($event->getReplyToken(), $text_message);
                                break;

                            case '編輯':
                                $old_event = Event::query()->where('id', $data->event_id)->first();

                                if (!$old_event) {
                                    $text_message = new TextMessageBuilder("編輯待辦事項失敗");

                                    $bot->replyMessage($event->getReplyToken(), $text_message);
                                } else {
                                    $old_event->event    = $data->event;
                                    $old_event->deadline = $data->deadline ?: null;

                                    $old_event->save();

                                    $text_message = new TextMessageBuilder("編輯待辦事項成功：\n" . $old_event->event);

                                    $bot->replyMessage($event->getReplyToken(), $text_message);
                                }

                                break;

                            default:
                                $text_message = new TextMessageBuilder($action);

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

                            $bot->replyMessage($event->getReplyToken(), new TextMessageBuilder("待辦事項已完成：\n" . $title));
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
        ]);
    }
}
