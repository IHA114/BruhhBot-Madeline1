<?php
/**
 * Copyright (C) 2016-2017 Hunter Ashton
 * This file is part of BruhhBot.
 * BruhhBot is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * BruhhBot is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */




function muteme($update, $MadelineProto, $msg = '', $send = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['muteme']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $fromid = cache_from_user_info($update, $MadelineProto);
        if (!isset($fromid['bot_api_id'])) {
            return;
        }
        $fromid = $fromid['bot_api_id'];
        $from_name = catch_id($update, $MadelineProto, $fromid)[2];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        if (is_moderated($ch_id) && is_bot_admin($update, $MadelineProto) && from_admin_mod($update, $MadelineProto, $mods, true) && bot_present($update, $MadelineProto)) {
            if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                $id = catch_id($update, $MadelineProto, $msg);
                if ($id[0]) {
                    $userid = $id[1];
                }
                if (isset($userid)) {
                    $mutemod = $MadelineProto->responses['muteme']['mutemod'];
                    if (!is_admin_mod(
                        $update,
                        $MadelineProto,
                        $userid,
                        $mutemod,
                        true
                    )
                    ) {
                        $username = $id[2];
                        $mention = html_mention($username, $userid);
                        check_json_array('mutelist.json', $ch_id);
                        $file = file_get_contents('mutelist.json');
                        $mutelist = json_decode($file, true);
                        if (isset($mutelist[$ch_id])) {
                            if (!in_array($userid, $mutelist[$ch_id])) {
                                array_push($mutelist[$ch_id], $userid);
                                file_put_contents(
                                    'mutelist.json',
                                    json_encode($mutelist)
                                );
                                $str = $MadelineProto->responses['muteme']['success'];
                                $repl = [
                                    'mention' => $mention,
                                ];
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                                $alert = "<code>$from_name muted $username in $title</code>";
                            } else {
                                $str = $MadelineProto->responses['muteme']['already'];
                                $repl = [
                                    'mention' => $mention,
                                ];
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } else {
                            $mutelist[$ch_id] = [];
                            array_push($mutelist[$ch_id], $userid);
                            file_put_contents(
                                'mutelist.json',
                                json_encode($mutelist)
                            );
                            $str = $MadelineProto->responses['muteme']['success'];
                            $repl = [
                                'mention' => $mention,
                            ];
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                            $alert = "<code>$from_name muted $username in $title</code>";
                        }
                    }
                } else {
                    $str = $MadelineProto->responses['muteme']['idk'];
                    $repl = [
                        'msg' => $msg,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                }
            } else {
                $message = $MadelineProto->responses['muteme']['help'];
                $default['message'] = $message;
            }
        }
        if (isset($default['message']) && $send) {
            $sentMessage = $MadelineProto->messages->sendMessage($default);
        }
        if (isset($sentMessage) && $send) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
        if (isset($alert)) {
            alert_moderators($MadelineProto, $ch_id, $alert);
        }
    }
}

function unmuteme($update, $MadelineProto, $msg = '')
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['unmuteme']['mods'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            $fromid = cache_from_user_info($update, $MadelineProto);
            if (!isset($fromid['bot_api_id'])) {
                return;
            }
            $fromid = $fromid['bot_api_id'];
            $from_name = catch_id($update, $MadelineProto, $fromid)[2];
            $default = [
                'peer'            => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode'      => 'html',
            ];
            if (is_moderated($ch_id) && is_bot_admin($update, $MadelineProto) && from_admin_mod($update, $MadelineProto, $mods, true)) {
                if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                    $id = catch_id($update, $MadelineProto, $msg);
                    if ($id[0]) {
                        $userid = $id[1];
                    }
                    if (isset($userid)) {
                        $username = $id[2];
                        $mention = html_mention($username, $userid);
                        check_json_array('mutelist.json', $ch_id);
                        $file = file_get_contents('mutelist.json');
                        $mutelist = json_decode($file, true);
                        if (isset($mutelist[$ch_id])) {
                            if (in_array($userid, $mutelist[$ch_id])) {
                                if (($key = array_search(
                                    $userid,
                                    $mutelist[$ch_id]
                                )) !== false
                                ) {
                                    unset($mutelist[$ch_id][$key]);
                                }
                                file_put_contents(
                                    'mutelist.json',
                                    json_encode($mutelist)
                                );
                                $str = $MadelineProto->responses['unmuteme']['success'];
                                $repl = [
                                    'mention' => $mention,
                                ];
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                                $alert = "<code>$from_name unmuted $username in $title</code>";
                            } else {
                                $str = $MadelineProto->responses['unmuteme']['already'];
                                $repl = [
                                    'mention' => $mention,
                                ];
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } else {
                            $str = $MadelineProto->responses['unmuteme']['already'];
                            $repl = [
                                'mention' => $mention,
                            ];
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                        }
                    }
                } else {
                    $message = $MadelineProto->responses['unmuteme']['help'];
                    $default['message'] = $message;
                }
            }
            if (isset($default['message'])) {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
            if (isset($alert)) {
                alert_moderators($MadelineProto, $ch_id, $alert);
            }
        }
    }
}

function muteall($update, $MadelineProto, $send = true)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['muteall']['mods'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            $default = [
                'peer'            => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode'      => 'html',
                ];
            $userid = 'all';
            $fromid = cache_from_user_info($update, $MadelineProto);
            if (!isset($fromid['bot_api_id'])) {
                return;
            }
            $fromid = $fromid['bot_api_id'];
            $from_name = catch_id($update, $MadelineProto, $fromid)[2];
            if (is_moderated($ch_id) && is_bot_admin($update, $MadelineProto) && from_admin_mod($update, $MadelineProto, $mods, true)) {
                check_json_array('mutelist.json', $ch_id);
                $file = file_get_contents('mutelist.json');
                $mutelist = json_decode($file, true);
                if (isset($mutelist[$ch_id])) {
                    if (!in_array($userid, $mutelist[$ch_id])) {
                        array_push($mutelist[$ch_id], $userid);
                        file_put_contents(
                            'mutelist.json',
                            json_encode($mutelist)
                        );
                        $message = $MadelineProto->responses['muteall']['success'];
                        $default['message'] = $message;
                        $alert = "<code>$from_name muted EVERYONE in $title</code>";
                    } else {
                        $message = $MadelineProto->responses['muteall']['already'];
                        $default['message'] = $message;
                    }
                } else {
                    $mutelist[$ch_id] = [];
                    array_push($mutelist[$ch_id], $userid);
                    file_put_contents(
                        'mutelist.json',
                        json_encode($mutelist)
                    );
                    $message = $MadelineProto->responses['muteall']['success'];
                    $default['message'] = $message;
                }
            }
            if (isset($default['message']) && $send) {
                $sentMessage = $MadelineProto->messages->sendMessage($default);
            }
            if (isset($sentMessage) && $send) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
            if (isset($alert)) {
                alert_moderators($MadelineProto, $ch_id, $alert);
            }
        }
    }
}

function unmuteall($update, $MadelineProto)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['unmuteall']['mods'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            $userid = 'all';
            $fromid = cache_from_user_info($update, $MadelineProto);
            if (!isset($fromid['bot_api_id'])) {
                return;
            }
            $fromid = $fromid['bot_api_id'];
            $from_name = catch_id($update, $MadelineProto, $fromid)[2];
            $default = [
                'peer'            => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode'      => 'html',
            ];
            if (is_moderated($ch_id) && is_bot_admin($update, $MadelineProto) && from_admin_mod($update, $MadelineProto, $mods, true)) {
                check_json_array('mutelist.json', $ch_id);
                $file = file_get_contents('mutelist.json');
                $mutelist = json_decode($file, true);
                if (isset($mutelist[$ch_id])) {
                    if (in_array($userid, $mutelist[$ch_id])) {
                        if (($key = array_search(
                            $userid,
                            $mutelist[$ch_id]
                        )) !== false
                        ) {
                            unset($mutelist[$ch_id][$key]);
                        }
                        file_put_contents(
                            'mutelist.json',
                            json_encode($mutelist)
                        );
                        $message = $MadelineProto->responses['unmuteall']['success'];
                        $default['message'] = $message;
                        $alert = "<code>$from_name unmuted EVERYONE in $title</code>";
                    } else {
                        $message = $MadelineProto->responses['unmuteall']['already'];
                        $default['message'] = $message;
                    }
                } else {
                    $message = $MadelineProto->responses['unmuteall']['already'];
                    $default['message'] = $message;
                }
            }
            if (isset($default['message'])) {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
            if (isset($alert)) {
                alert_moderators($MadelineProto, $ch_id, $alert);
            }
        }
    }
}

function getmutelist($update, $MadelineProto)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            $default = [
                'peer'            => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode'      => 'html',
            ];
            if (is_moderated($ch_id)) {
                check_json_array('mutelist.json', $ch_id);
                $file = file_get_contents('mutelist.json');
                $mutelist = json_decode($file, true);
                if (isset($mutelist[$ch_id])) {
                    if (!in_array('all', $mutelist[$ch_id])) {
                        foreach ($mutelist[$ch_id] as $i => $key) {
                            $username = catch_id($update, $MadelineProto, $key)[2];
                            $mention = html_mention($username, $key);
                            if (!isset($message)) {
                                $str = $MadelineProto->responses['getmutelist']['header'];
                                $repl = [
                                    'title' => $title,
                                ];
                                $message = $MadelineProto->engine->render($str, $repl);
                                $message = $message."$mention - $key\r\n";
                            } else {
                                $message = $message."$mention - $key\r\n";
                            }
                        }
                    } else {
                        $message = $MadelineProto->responses['getmutelist']['dictatorship'];
                        $default['message'] = $message;
                    }
                }
                if (!isset($message)) {
                    $str = $MadelineProto->responses['getmutelist']['none'];
                    $repl = [
                        'title' => $title,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                } else {
                    $default['message'] = $message;
                }
            }
            if (isset($default['message'])) {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}
