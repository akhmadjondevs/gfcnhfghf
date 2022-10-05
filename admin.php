<?php
$admin_step = $tool->getSession($chat_id);
$temp = $tool->getTempData($chat_id);

$bot_commands =   ["📊 Statistika", "↗️ Xabar yuborish", "↗️ Xabar yuborish xolati", "✅ Yuborilgan xabarlar ro'yxati", "/start", "/panel"];

$keyboard = $bot->buildKeyBoard(
    [
        [['text' => "📊 Statistika"]],
        [['text' => "↗️ Xabar yuborish"], ['text' => "↗️ Xabar yuborish xolati"]],
        [['text' => "✅ Yuborilgan xabarlar ro'yxati"]]
    ],
    true,
    true
);
if (isset($update->message)) {
if (isset($text) and in_array($update->message->text, $bot_commands)) {
    goto panel;
}
    if ($admin_step == "message") {
        $temp['chat_id'] = $chat_id;
        $temp['msg_id'] = $mid;
        $temp['keyboard'] = "empty";
        if ($temp['type'] == "copymessage" and !empty($update->message->reply_markup)) {
            $mkey = base64_encode(json_encode($update->message->reply_markup, JSON_PRETTY_PRINT));
            $temp['keyboard'] = $mkey;
        }
        $tool->setTempData($chat_id, $temp);
        $tool->stopSession($chat_id);
        $tur = $temp['type'] == "copymessage" ? "Oddiy" : "Forward";
        $bot->sendMessage([
            'chat_id' => $chat_id,
            'text' => "<b>✅ Xabar qabul qilindi, endi nima qilamiz?\n\nXabar turi $tur</b>",
            'parse_mode' => "html",
            'reply_markup' => $bot->buildInlineKeyBoard([
                [['text' => "Yuborishni boshlash ↗️", 'callback_data' => "sendtemp"]],
                [['text' => "Xabarni ko'rish 👀", 'callback_data' => "view:temp"]],
                [['text' => "Bekor qilish (o'chirish) ❌", 'callback_data' => "cancel"]]
            ])
        ]);
        exit;
    }
}
panel:
if (isset($text)) {
    if ($text == "/panel") {
        $bot->sendMessage([
            'chat_id' => $chat_id,
            'text' => "<b>$firstname admin panelga xush kelibsiz.</b>",
            'parse_mode' => "html",
            'reply_markup' => $keyboard
        ]);
        $tool->stopSession($chat_id);
        $tool->stopTempData($chat_id);
        exit;
    } elseif ($text == "↗️ Xabar yuborish") {
        $send = $tool->getSession("send");
        if ($send == "true") {
            $send = mysqli_fetch_assoc($tool->query("SELECT * FROM `send` ORDER BY `id` DESC"));
            $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "<b>Hozirda xabar yuborilyapti yuborib bo'lingandan so'ng yangi xabar yuborishingiz mumkin. Yoki xabar yuborishni to'xtatib yangisini yuborishingiz mumkin.</b>",
                'parse_mode' => "html",
                'reply_markup' => $keyboard,
                'reply_to_message_id' => ($send['chat_id'] == $chat_id) ? $send['info_msg'] : null
            ]);
            exit;
        } elseif ($send == "false") {
            $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "<b>Kimlarga xabar yuboramiz?</b>",
                'parse_mode' => "html",
                'reply_markup' => $bot->buildInlineKeyBoard([
                    [['text' => "👤 Foydalanuvchilarga", 'callback_data' => "sendtype:users"]],
                    [['text' => "❌ Bekor qilish", 'callback_data' => "cancel"]]
                ])
            ]);
            $tool->setSession($chat_id, "sendtype");
            exit;
        }
    } elseif ($text == "📊 Statistika") {
        $users = mysqli_num_rows($tool->select_all("users"));
        $sending_posts = mysqli_num_rows($tool->select_all("send"));
        $bot->sendMessage([
            'chat_id' => $chat_id,
            'text' => "<b>📊 Umumiy statistika:</b>\n\n<b>👤 Foydalanuvchilar:</b> $users ta\n<b>↗️ Yuborilgan xabarlar:</b> $sending_posts ta",
            'parse_mode' => "html",
            'reply_markup' => $keyboard
        ]);
        exit;
    } elseif ($text == "↗️ Xabar yuborish xolati") {
        $send = $tool->getSession("send");
        if ($send == "true") {
            $post = mysqli_fetch_assoc($tool->query("SELECT * FROM `send` ORDER BY `id` DESC"));
            $sender = $bot->getChat(['chat_id' => $post['chat_id']])['result'];
            $yuboruvchi = (!empty($sender['username'])) ? "<a href='t.me/{$sender['username']}'>{$sender['first_name']}</a>" : "<a href='tg://user?id={$sender['id']}'>{$sender['first_name']}</a>";
            $content = [
                'chat_id' => $chat_id,
                'text' => "<b>✅ Hozirda xabar yuborilmoqda.</b>\n\n👨🏻‍💻 Yuboruvchi $yuboruvchi",
                'parse_mode' => "html",
                'disable_web_page_preview' => true,
                'reply_markup' => $bot->buildInlineKeyBoard([
                    [['text' => "👀 Xabarni ko'rish", 'callback_data' => "view:{$post['id']}"]],
                    [['text' => "❌ Yuborishni toxtatish", 'callback_data' => "stop:{$post['id']}"]]
                ])
            ];
            $content['reply_to_message_id'] = ($post['chat_id'] == $chat_id) ? $post['info_msg'] : null;
            $bot->sendMessage($content);
            exit;
        } elseif ($send == "false") {
            $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "<b>✅ Hozirda xabar yuborilmayapti. Xabar yuborishingiz mumkin</b>",
                'parse_mode' => "html",
                'reply_markup' => $keyboard
            ]);
            exit;
        }
    } elseif ($text == "✅ Yuborilgan xabarlar ro'yxati") {
        $posts = $tool->query("SELECT * FROM `send` ORDER BY `id` DESC");
        $posts_count = mysqli_num_rows($posts);
        if ($posts_count == 0) {
            $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "<b>Bot orqali umuman xabar yuborilmagan!</b>",
                'parse_mode' => "html",
                'reply_markup' => $keyboard
            ]);
            exit;
        }
        $txt = "";
        $key = [];
        $i = 0;
        while ($post = mysqli_fetch_assoc($posts)) {
            if ($i++ > 9) break;
            if ($post['completed'] == "true") {
                $holat = "✅ Yakunlangan";
                $key[] = [['text' => "✅ " . $post['id'], 'callback_data' => "hidden"], ['text' => "👀 Xabarni ko'rish", 'callback_data' => "view:{$post['id']}"], ['text' => "🔂 Qayta jo'natish", 'callback_data' => "resend:{$post['id']}"]];
            } elseif ($post['completed'] == "false") {
                $holat = "☑️ Yuborilmoqda";
                $key[] = [['text' => "☑️ " . $post['id'], 'callback_data' => "hidden"], ['text' => "👀 Xabarni ko'rish", 'callback_data' => "view:{$post['id']}"], ['text' => "❌ Yuborishni to'xtatish", 'callback_data' => "stop:{$post['id']}"]];
            } elseif ($post['completed'] == "aborted") {
                $holat = "🚫 To'xtatilgan";
                $key[] = [['text' => "🚫 " . $post['id'], 'callback_data' => "hidden"], ['text' => "👀 Xabarni ko'rish", 'callback_data' => "view:{$post['id']}"], ['text' => "🔂 Qayta jo'natish", 'callback_data' => "resend:{$post['id']}"]];
            }

            $tur = ($post['send_type'] == "users") ? "👤 Foydalanuvchilarga" : "👥 Guruhlarga";
            $txt .= "<i>$post[id]</i>) $tur — ($post[time]) — $holat\n\n";
        }
        if ($posts_count > 10) $key[] = [['text' => "🔴", 'callback_data' => "hidden"], ['text' => "1/" . ceil($posts_count / 10), 'callback_data' => "hidden"], ['text' => "➡️", 'callback_data' => "nextposts:10"]];
        $key[] = [['text' => "↩️ Bosh sahifaga qaytish", 'callback_data' => "cancel"]];
        $bot->sendMessage([
            'chat_id' => $chat_id,
            'text' => $txt,
            'parse_mode' => "html",
            'reply_markup' => $bot->buildInlineKeyBoard($key)
        ]);
        exit;
    }
} elseif (isset($data)) {
    if ($data == "hidden") exit(200);
    $data2 = explode(":", $data);
    if ($data == "cancel") {
        unset($data);
        $text = "/panel";
        goto panel;
    } elseif ($data == "view:temp") {
        if ($temp == false) {
            $bot->editMessageText(['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "<b>Xabar mavjud emas!</b>", 'parse_mode' => "html", "reply_markup" => $keyboard]);
            exit(200);
        }
        del();
        $content = [
            'chat_id' => $chat_id,
            'from_chat_id' => $temp['chat_id'],
            'message_id' => $temp['msg_id']
        ];
        if ($temp['type'] == "copymessage") {
            $content['reply_markup'] = (empty($temp['keyboard'])) ? null : base64_decode($temp['keyboard']);
            $bot->copyMessage($content);
        } elseif ($temp['type'] == "forward") {
            $bot->forwardMessage($content);
        }
        $tur = $temp['type'] == "copymessage" ? "Oddiy" : "Forward";
        $bot->sendMessage([
            'chat_id' => $chat_id,
            'text' => "<b>✅ Nima qilamiz?\n\nXabar turi $tur</b>",
            'parse_mode' => "html",
            'reply_markup' => $bot->buildInlineKeyBoard([
                [['text' => "Yuborishni boshlash ↗️", 'callback_data' => "sendtemp"]],
                [['text' => "Xabarni ko'rish 👀", 'callback_data' => "view:temp"]],
                [['text' => "Bekor qilish (o'chirish) ❌", 'callback_data' => "cancel"]]
            ])
        ]);
        exit;
    } elseif ($data == "sendtemp") {
        del();
        if ($temp == false) {
            $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "<b>Ma'lumotlar mavjud emas!</b>",
                'parse_mode' => "html",
                'reply_markup' => $keyboard
            ]);
            exit;
        }
        $info_msg = $bot->sendMessage([
            'chat_id' => $chat_id,
            'text' => "<b>Xabar yuborish 1 daqiqadan so'ng boshlanadi ✅</b>\n\n<i><b>Ushbu xabarni o'chirmang!!!</b></i>",
            'parse_mode' => "html"
        ])['result']['message_id'];
        $bot->pinChatMessage([
            'chat_id' => $chat_id,
            'message_id' => $info_msg,
            'disable_notification' => false
        ]);
        $tool->insert("send", ['chat_id' => $temp['chat_id'], 'msg_id' => $temp['msg_id'], 'send_type' => $temp['send_type'], 'keyboard' => $temp['keyboard'], 'completed' => "false", 'type' => $temp['type'], 'info_msg' => $info_msg, 'time' => date("Y-m-d H:i:s")]);
        $tool->setSession("send", "true");
        $tool->stopTempData($chat_id);
        $tool->stopSession($chat_id);
        exit;
    } elseif (mb_stripos($data, "nextposts:") !== false) {
        del();
        $posts = $tool->query("SELECT * FROM `send` ORDER BY `id` DESC LIMIT {$data2[1]}, 11");
        $posts_count = mysqli_num_rows($posts);
        $all = mysqli_num_rows($tool->select_all("send"));
        $txt = "";
        $key = [];
        $i = 0;
        while ($post = mysqli_fetch_assoc($posts)) {
            if ($i++ > 9) break;
            if ($post['completed'] == "true") {
                $holat = "✅ Yakunlangan";
                $key[] = [['text' => "✅ " . $post['id'], 'callback_data' => "hidden"], ['text' => "👀 Xabarni ko'rish", 'callback_data' => "view:{$post['id']}"], ['text' => "🔂 Qayta jo'natish", 'callback_data' => "resend:{$post['id']}"]];
            } elseif ($post['completed'] == "false") {
                $holat = "☑️ Yuborilmoqda";
                $key[] = [['text' => "☑️ " . $post['id'], 'callback_data' => "hidden"], ['text' => "👀 Xabarni ko'rish", 'callback_data' => "view:{$post['id']}"], ['text' => "❌ Yuborishni to'xtatish", 'callback_data' => "stop:{$post['id']}"]];
            } elseif ($post['completed'] == "aborted") {
                $holat = "🚫 To'xtatilgan";
                $key[] = [['text' => "🚫 " . $post['id'], 'callback_data' => "hidden"], ['text' => "👀 Xabarni ko'rish", 'callback_data' => "view:{$post['id']}"], ['text' => "🔂 Qayta jo'natish", 'callback_data' => "resend:{$post['id']}"]];
            }

            $tur = ($post['send_type'] == "users") ? "👤 Foydalanuvchilarga" : "👥 Guruhlarga";
            $txt .= "<i>{$post['id']}</i>) $tur — ({$post['time']}) — $holat\n\n";
        }

        $back = ($data2[1] == 0) ? ['text' => "🔴", 'callback_data' => "hidden"] : ['text' => "⬅️", 'callback_data' => "nextposts:" . ($data2[1] - 10)];
        $next = ($posts_count > 10) ? ['text' => "➡️", 'callback_data' => "nextposts:" . ($data2[1] + 10)] : ['text' => "🔴", 'callback_data' => "hidden"];

        $qush = ($data2[1] == 0) ? 0 : 1;
        if ($data2[1] == 0) $data2[1] = 10;
        $key[] = [$back, ['text' => ceil($data2[1] / 10) + $qush . "/" . ceil($all / 10), 'callback_data' => "hidden"], $next];
        $key[] = [['text' => "↩️ Bosh sahifaga qaytish", 'callback_data' => "cancel"]];

        $bot->sendMessage([
            'chat_id' => $chat_id,
            'text' => $txt,
            'parse_mode' => "html",
            'reply_markup' => $bot->buildInlineKeyBoard($key)
        ]);
        exit;
    } elseif (mb_stripos($data, "view:") !== false) {
        del();
        $post = mysqli_fetch_assoc($tool->select("send", ['id' => $data2[1]]));
        if ($post == false) {
            $bot->sendMessage(['chat_id' => $chat_id, 'text' => "<b>Xabar mavjud emas!</b>", 'parse_mode' => "html", "reply_markup" => $keyboard]);
            exit(200);
        }
        $content = [
            'chat_id' => $chat_id,
            'from_chat_id' => $post['chat_id'],
            'message_id' => $post['msg_id']
        ];
        if ($post['type'] == "copymessage") {
            $content['reply_markup'] = (empty($post['keyboard']) or $post['keyboard'] == "empty") ? null : base64_decode($post['keyboard']);
            $bot->copyMessage($content);
        } elseif ($post['type'] == "forward") {
            $bot->forwardMessage($content);
        }
        $tur = $post['type'] == "copymessage" ? "Oddiy" : "Forward";
        $bot->sendMessage([
            'chat_id' => $chat_id,
            'text' => "<b>✅ Nima qilamiz?\n\nXabar turi $tur</b>",
            'parse_mode' => "html",
            'reply_markup' => $bot->buildInlineKeyBoard([
                [['text' => "🔂 Qayta jo'natish", 'callback_data' => "resend:" . $post['id']]],
                ($post['completed'] == "false") ? [['text' => "❌ Yuborishni toxtatish", 'callback_data' => "stop:{$post['id']}"]] : [['text' => "", 'callback_data' => "hidden"]],
                [['text' => "↩️ Bosh sahifaga qaytish", 'callback_data' => "cancel"]]
            ])
        ]);
        exit;
    } elseif (mb_stripos($data, "resend:") !== false) {
        $send = $tool->getSession("send");
        if ($send == "true") {
            $send = mysqli_fetch_assoc($tool->query("SELECT * FROM `send` ORDER BY `id` DESC"));
            $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "<b>Hozirda xabar yuborilyapti yuborib bo'lingandan so'ng yangi xabar yuborishingiz mumkin. Yoki xabar yuborishni to'xtatib yangisini yuborishingiz mumkin.</b>",
                'parse_mode' => "html",
                'reply_markup' => $keyboard,
                'reply_to_message_id' => ($send['chat_id'] == $chat_id) ? $send['info_msg'] : null
            ]);
        } elseif ($send == "false") {
            $post = mysqli_fetch_assoc($tool->select("send", ['id' => $data2[1]]));
            unset($post['id']);
            del();
            $info_msg = $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "<b>Xabar yuborish 1 daqiqadan so'ng boshlanadi ✅</b>\n\n<i><b>Ushbu xabarni o'chirmang!!!</b></i>",
                'parse_mode' => "html"
            ])['result']['message_id'];
            $bot->pinChatMessage([
                'chat_id' => $chat_id,
                'message_id' => $info_msg,
                'disable_notification' => false
            ]);
            $post['chat_id'] = $chat_id;
            $post['info_msg'] = $info_msg;
            $post['completed'] = "false";
            $post['last_id'] = 0;
            $post['sent'] = 0;
            $post['not_sent'] = 0;
            $post['time'] = date("Y-m-d H:i:s");
            $post['end_time'] = "";
            $tool->insert("send", $post);
            $tool->setSession("send", "true");
        }
        exit;
    } elseif (mb_stripos($data, "stop:") !== false) {
        del();
        $post = mysqli_fetch_assoc($tool->select("send", ['id' => $data2[1]]));
        if (!$post) {
            $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "<b>Ushbu xabar topilmadi.</b>",
                'parse_mode' => "html",
                'reply_markup' => $keyboard
            ]);
        } elseif ($post['completed'] == "true") {
            $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "<b>Ushbu xabarni yuborish yakunlab bo'lingan.</b>",
                'parse_mode' => "html",
                'reply_markup' => $keyboard
            ]);
        } elseif ($post['completed'] == "aborted") {
            $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "<b>Ushbu xabarni yuborish allaqachon to'xtatib bo'lingan.</b>",
                'parse_mode' => "html",
                'reply_markup' => $keyboard
            ]);
        } elseif ($post['completed'] == "false") {
            $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => "<b>Xabarni yuborish yakunlandi.</b>",
                'parse_mode' => "html",
                'reply_markup' => $keyboard
            ]);
            $tool->setSession("send", "false");
            $tool->update("send", ['completed' => "aborted"], ['id' => $data2[1]]);
        }
        exit;
    } elseif ($admin_step == "sendtype" and mb_stripos($data, "sendtype") !== false) {
        $tool->setTempData($chat_id, ['send_type' => $data2[1]]);
        $tool->setSession($chat_id, "type");
        $bot->editMessageText([
            'message_id' => $mid,
            'chat_id' => $chat_id,
            'text' => "<b>Xabarni qanday ko'rinishda yuboramiz?</b>",
            'parse_mode' => "html",
            'reply_markup' => $bot->buildInlineKeyBoard([
                [['text' => "Forward ko'rinishida ⏩", 'callback_data' => "type:forward"], ['text' => "⏺ Oddiy ko'rinishda", 'callback_data' => "type:copymessage"]],
                [['text' => "❌ Bekor qilish", 'callback_data' => "cancel"]]
            ])
        ]);
        exit;
    } elseif ($admin_step == "type" and mb_stripos($data, "type:") !== false) {
        $temp['type'] = $data2[1];
        $tool->setTempData($chat_id, $temp);
        $tool->setSession($chat_id, "message");
        $bot->editMessageText([
            'message_id' => $mid,
            'chat_id' => $chat_id,
            'text' => "<b>Marhamat yuborilishi kerak bo'lgan postni yuboring.</b>",
            'parse_mode' => "html",
            'reply_markup' => $bot->buildInlineKeyBoard([
                [['text' => "❌ Bekor qilish", 'callback_data' => "cancel"]]
            ])
        ]);
        exit;
    }
}
