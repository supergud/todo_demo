<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\UnfollowEvent;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

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
                    $user->delete();
                }
            } elseif ($event instanceof MessageEvent) {
                $message_type = $event->getMessageType();

                switch ($message_type) {
                    case 'text':
                        $text = $event->getText();

                        switch ($text) {
                            default:
                                $text_message = new TextMessageBuilder($text);
                                $bot->replyMessage($event->getReplyToken(), $text_message);
                                break;
                        }

                        break;
                }
            } else {
                $text_message = new TextMessageBuilder('不要亂玩...');

                $bot->replyMessage($event->getReplyToken(), $text_message);
            }
        }

        return response()->json([
            'state' => true,
        ]);
    }
}
