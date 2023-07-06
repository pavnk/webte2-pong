<?php
//php server2.php start
use Workerman\Worker;
use Workerman\Lib\Timer;

require_once __DIR__ . '/vendor/autoload.php';

// SSL context.
$context = [
    'ssl' => [
        'local_cert'  => '/home/xpavlisn/webte_fei_stuba_sk.pem',
        'local_pk'    => '/home/xpavlisn/webte.fei.stuba.sk.key',
        'verify_peer' => false,
    ]
];

// Create A Worker and Listens 9000 port, use Websocket protocol
$ws_worker = new Worker("websocket://0.0.0.0:9000", $context);

// Enable SSL. WebSocket+SSL means that Secure WebSocket (wss://).
// The similar approaches for Https etc.
$ws_worker->transport = 'ssl';

// 4 processes
$ws_worker->count = 1;

$x = "";
$y = "";
$playerID = "";

// Add a Timer to Every worker process when the worker process start
$ws_worker->onWorkerStart = function($ws_worker)
{   $GLOBALS['userdata']=0;
    $players[] = array();
    $playerOrder = 0;
    $gameStarted = false;
    $ball[] = array();
    $vector[] = array();
    $spectators[] = array();
    $activeConnections = 0;
    $bounceCount = 0;
    $firstPlayerIndex = 0;
    $spectatorOrder = 0;
    $speedBonus=0.01;
    // Timer every 5 seconds
    Timer::add(1/60, function() use(&$players, $ws_worker, &$gameStarted, &$ball, &$vector,&$bounceCount, &$firstPlayerIndex,
    &$playerOrder, &$spectators, &$spectatorOrder, &$activeConnections, &$speedBonus)
    {
        // Iterate over connections and send the time
        if($gameStarted === true){
            foreach($ws_worker->connections as $connection)
            {
                //$connection->send(generateRandomNumberJsonMessage($GLOBALS['userdata']));
                $connection->send(json_encode(array("type" => "gameUpdate", "players" => $players, "ball" => $ball)));

            }
            $prev_x = $ball["x"];
            $prev_y = $ball["y"];
            $ball["speed"] += 0.01;
            $ball["x"] = $ball["x"] + $vector["x"] * $ball["speed"];
            $ball["y"] = $ball["y"] + $vector["y"] * $ball["speed"];
            
            if ($ball["x"] < 50 && $prev_x >= 50){
                //case player dead, case player alive, in player alive touched corner, player or went through
                $arrorder=null;
                for($i=0;$i<count($players);$i++){
                    if($players[$i]["playerOrder"]===1){
                        $arrorder = $i;
                    }
                }
                if($arrorder === null){
                    //player not existing, bounce from whole wall
                    $angle = atan2($vector["y"], $vector["x"]);
                    $addition = deg2rad(rand(-10, 10));
                    $angle = $angle + $addition;
                    $vector["x"] = abs(cos($angle));
                    $vector["y"] = -1 * sin($angle);
                    $bounceCount++;
                } else{
                    //player exists
                    if(($ball["y"]>=$players[$arrorder]["y"]-30 && $ball["y"]<=$players[$arrorder]["y"]+100) ||
                        ($ball["y"]>=50 && $ball["y"]<=100 || $ball["y"]>=500 && $ball["y"]<=550)){
                        $angle = atan2($vector["y"], $vector["x"]);
                        $addition = deg2rad(rand(-10, 10));
                        $angle = $angle + $addition;
                        $vector["x"] = abs(cos($angle));
                        $vector["y"] = -1 * sin($angle);
                        $bounceCount++;
                    } else{
                        //reset
                        $players[$arrorder]["health"] = $players[$arrorder]["health"]-1;
                        $ball["x"] = 600/2-15;
                        $ball["y"] = 600/2-15;
                        $ball["speed"] = 5;
                        $rand = rand(0, 3);
                        if($rand === 0){
                            $vector["x"] = 0;
                            $vector["y"] = 1;
                        } else if($rand === 1){
                            $vector["x"] = 1;
                            $vector["y"] = 0;
                        } else if($rand === 2){
                            $vector["x"] = 0;
                            $vector["y"] = -1;
                        } else if($rand === 3){
                            $vector["x"] = -1;
                            $vector["y"] = 0;
                        }
                    }
                }
            }

            if ($ball["x"] > 520 && $prev_x <= 520){
                //case player dead, case player alive, in player alive touched corner, player or went through
                $arrorder=null;
                for($i=0;$i<count($players);$i++){
                    if($players[$i]["playerOrder"]===2){
                        $arrorder = $i;
                    }
                }
                if($arrorder === null){
                    //player not existing, bounce from whole wall
                    $angle = atan2($vector["y"], $vector["x"]);
                    $addition = deg2rad(rand(-10, 10));
                    $angle = $angle + $addition;
                    $vector["x"] = -1 * cos($angle);
                    $vector["y"] = sin($angle);
                    $bounceCount++;
                } else{
                    if(($ball["y"]>=$players[$arrorder]["y"]-30 && $ball["y"]<=$players[$arrorder]["y"]+100) ||
                        ($ball["y"]>=50 && $ball["y"]<=100 || $ball["y"]>=500 && $ball["y"]<=550)){
                        $angle = atan2($vector["y"], $vector["x"]);
                        $addition = deg2rad(rand(-10, 10));
                        $angle = $angle + $addition;
                        $vector["x"] = -1 * cos($angle);
                        $vector["y"] = sin($angle);
                        $bounceCount++;
                    } else{
                        //reset
                        $players[$arrorder]["health"] = $players[$arrorder]["health"]-1;
                        $ball["x"] = 600/2-15;
                        $ball["y"] = 600/2-15;
                        $ball["speed"] = 5;
                        $rand = rand(0, 3);
                        if($rand === 0){
                            $vector["x"] = 0;
                            $vector["y"] = 1;
                        } else if($rand === 1){
                            $vector["x"] = 1;
                            $vector["y"] = 0;
                        } else if($rand === 2){
                            $vector["x"] = 0;
                            $vector["y"] = -1;
                        } else if($rand === 3){
                            $vector["x"] = -1;
                            $vector["y"] = 0;
                        }
                    }
                }
            }
            if ($ball["y"] < 50 && $prev_y >= 50){
                $arrorder=null;
                for($i=0;$i<count($players);$i++){
                    if($players[$i]["playerOrder"]===3){
                        $arrorder = $i;
                    }
                }
                if($arrorder === null){
                    $angle = atan2($vector["x"], $vector["y"]);
                    $addition = deg2rad(rand(-10, 10));
                    $angle = $angle + $addition;
                    $vector["x"] = -1 * sin($angle);
                    $vector["y"] = abs(cos($angle));
                    $bounceCount++;
                } else{
                    //player exists
                    if(($ball["x"]>=$players[$arrorder]["x"]-30 && $ball["x"]<=$players[$arrorder]["x"]+100) ||
                        ($ball["x"]>=50 && $ball["x"]<=100 || $ball["x"]>=500 && $ball["x"]<=550)){
                        $angle = atan2($vector["x"], $vector["y"]);
                        $addition = deg2rad(rand(-10, 10));
                        $angle = $angle + $addition;
                        $vector["x"] = -1 * sin($angle);
                        $vector["y"] = abs(cos($angle));
                        $bounceCount++;
                    } else{
                        //reset
                        $players[$arrorder]["health"] = $players[$arrorder]["health"]-1;
                        $ball["x"] = 600/2-15;
                        $ball["y"] = 600/2-15;
                        $ball["speed"] = 5;
                        $rand = rand(0, 3);
                        if($rand === 0){
                            $vector["x"] = 0;
                            $vector["y"] = 1;
                        } else if($rand === 1){
                            $vector["x"] = 1;
                            $vector["y"] = 0;
                        } else if($rand === 2){
                            $vector["x"] = 0;
                            $vector["y"] = -1;
                        } else if($rand === 3){
                            $vector["x"] = -1;
                            $vector["y"] = 0;
                        }
                    }
                }
            }
            if ($ball["y"] > 520 && $prev_y <= 520){
                $arrorder=null;
                for($i=0;$i<count($players);$i++){
                    if($players[$i]["playerOrder"]===4){
                        $arrorder = $i;
                    }
                }
                if($arrorder === null){
                    $angle = atan2($vector["x"], -$vector["y"]);
                    $addition = deg2rad(rand(-10, 10));
                    $angle = $angle + $addition;
                    $vector["x"] = sin($angle);
                    $vector["y"] = -abs(cos($angle));
                    $bounceCount++;
                } else{
                    //player exists
                    if(($ball["x"]>=$players[$arrorder]["x"]-30 && $ball["x"]<=$players[$arrorder]["x"]+100) ||
                        ($ball["x"]>=50 && $ball["x"]<=100 || $ball["x"]>=500 && $ball["x"]<=550)){
                        $angle = atan2($vector["x"], -$vector["y"]);
                        $addition = deg2rad(rand(-10, 10));
                        $angle = $angle + $addition;
                        $vector["x"] = sin($angle);
                        $vector["y"] = -abs(cos($angle));
                        $bounceCount++;
                    } else{
                        //reset
                        $players[$arrorder]["health"] = $players[$arrorder]["health"]-1;
                        $ball["x"] = 600/2-15;
                        $ball["y"] = 600/2-15;
                        $ball["speed"] = 5;
                        $rand = rand(0, 3);
                        if($rand === 0){
                            $vector["x"] = 0;
                            $vector["y"] = 1;
                        } else if($rand === 1){
                            $vector["x"] = 1;
                            $vector["y"] = 0;
                        } else if($rand === 2){
                            $vector["x"] = 0;
                            $vector["y"] = -1;
                        } else if($rand === 3){
                            $vector["x"] = -1;
                            $vector["y"] = 0;
                        }
                    }
                }
            }
            foreach($ws_worker->connections as $connection)
            {
                //$connection->send(generateRandomNumberJsonMessage($GLOBALS['userdata']));
                $connection->send(json_encode(array("type" => "bounceUpdate", "bounces" => $bounceCount)));

            }
            if(count($players)===0){
                $gameStarted = false;
                $ball["x"] = 600/2-15;
                $ball["y"] = 600/2-15;
                $ball["speed"] = 5;
                $rand = rand(0, 3);
                if($rand === 0){
                    $vector["x"] = 0;
                    $vector["y"] = 1;
                } else if($rand === 1){
                    $vector["x"] = 1;
                    $vector["y"] = 0;
                } else if($rand === 2){
                    $vector["x"] = 0;
                    $vector["y"] = -1;
                } else if($rand === 3){
                    $vector["x"] = -1;
                    $vector["y"] = 0;
                }
                $players[] = array();
                $playerOrder = 0;
                $ball[] = array();
                $vector[] = array();
                $spectators[] = array();
                $activeConnections = 0;
                $bounceCount = 0;
                $firstPlayerIndex = 0;
                $spectatorOrder = 0;
            }
            for ($i = 0; $i < count($players); $i++) {
                if (isset($players[$i]["health"]) && $players[$i]["health"] === 0) {
                    unset($players[$i]);
                    $players = array_values($players);
                    foreach($ws_worker->connections as $connection)
                    {
                        $connection->send(json_encode(array("type" => "died", "order" => $i)));
                    }
                }
            }
        }
    });
    $ws_worker->onConnect = function($connection) use ($ws_worker, &$players, &$activeConnections)
    {
        $connection->onWebSocketConnect = function($connection) use ($ws_worker, &$players, &$activeConnections) {
            $activeConnections++;
            echo "New connection established for connection ID " . $connection->id . "\n";
            $connection->send(json_encode(array("type" => "joinSuccessful")));
            foreach($ws_worker->connections as $connection)
            {
                $connection->send(json_encode(array("type" => "updateActiveConnections", "activeConnections" => $activeConnections)));
            }
        };
    };

    $ws_worker->onMessage = function($connection, $data) use ($ws_worker, &$players, &$playerOrder, &$gameStarted, &$ball, &$vector, &$spectators, &$firstPlayerIndex,&$spectatorOrder) {

        $payload = json_decode($data);
        if ($payload->type === "updateCoordinates") {
            //plank cant go into wall
            $players[$payload->order-1]["x"] = $payload->x;
            $players[$payload->order-1]["y"] = $payload->y;
            if($payload->order == 1){
                if($players[$payload->order-1]["y"] > 400){
                    $players[$payload->order-1]["y"] = 400;
                } else if($players[$payload->order-1]["y"] < 100){
                    $players[$payload->order-1]["y"] = 100;
                }
            } elseif($payload->order == 2){
                if($players[$payload->order-1]["y"] > 400){
                    $players[$payload->order-1]["y"] = 400;
                } else if($players[$payload->order-1]["y"] < 100){
                    $players[$payload->order-1]["y"] = 100;
                }
            } elseif($payload->order == 3){
                if($players[$payload->order-1]["x"] > 400){
                    $players[$payload->order-1]["x"] = 400;
                } else if($players[$payload->order-1]["x"] < 100){
                    $players[$payload->order-1]["x"] = 100;
                }
            } elseif($payload->order == 4){
                if($players[$payload->order-1]["x"] > 400){
                    $players[$payload->order-1]["x"] = 400;
                } else if($players[$payload->order-1]["x"] < 100){
                    $players[$payload->order-1]["x"] = 100;
                }
            }
        } else if($payload->type === "join"){
            if (isset($payload->playerName)) {
                $playerOrder++;
                $playerId = $connection->id;
                $connection->send(json_encode(array("type" => "playerId", "playerId" => $playerId, "playerOrder" => $playerOrder)));

                //Initialize coordinates for player
                if($playerOrder == 1){
                    $firstPlayerIndex = 1;
                    $players[$playerOrder-1] = array("playerName" => $payload->playerName, "x" => 0, "y" => 250, "playerOrder" => 1, "health" => 3, "id"=>$connection->id);
                    $connection->send(json_encode(array("type" => "initializeCoordinates", "x" => 0, "y" => 250, "playerOrder" => 1)));
                } elseif ($playerOrder == 2){
                    $players[$playerOrder-1] = array("playerName" => $payload->playerName, "x" => 550, "y" => 250, "playerOrder" => 2, "health" => 3, "id"=>$connection->id);
                    $connection->send(json_encode(array("type" => "initializeCoordinates", "x" => 550, "y" => 250, "playerOrder" => 2)));
                } elseif ($playerOrder == 3){
                    $players[$playerOrder-1] = array("playerName" => $payload->playerName, "x" => 250, "y" => 0, "playerOrder" => 3, "health" => 3, "id"=>$connection->id);
                    $connection->send(json_encode(array("type" => "initializeCoordinates", "x" => 250, "y" => 0, "playerOrder" => 3)));
                } elseif ($playerOrder == 4){
                    $players[$playerOrder-1] = array("playerName" => $payload->playerName, "x" => 250, "y" => 550, "playerOrder" => 4, "health" => 3, "id"=>$connection->id);
                    $connection->send(json_encode(array("type" => "initializeCoordinates", "x" => 250, "y" => 550, "playerOrder" => 4)));
                }

                if($playerOrder == 4){
                    $gameStarted = true;
                    //spawn ball
                    $ball["x"] = 600/2-15;
                    $ball["y"] = 600/2-15;
                    $ball["speed"] = 5;
                    $vector["x"] = 1;
                    $vector["y"] = 0;
                    foreach($ws_worker->connections as $connection)
                    {
                        $connection->send(json_encode(array("type" => "gameStart", "players" => $players)));
                    }
                }
            }
        } else if($payload->type === "startGame"){
            for($i = 0; $i<count($players);++$i){
                if($players[$i]["playerOrder"] === $payload->order){
                    if($payload->order === $firstPlayerIndex){
                        $gameStarted = true;
                        //spawn ball
                        $ball["x"] = 600/2-15;
                        $ball["y"] = 600/2-15;
                        $ball["speed"] = 5;
                        $rand = rand(0, 3);
                        if($rand === 0){
                            $vector["x"] = 0;
                            $vector["y"] = 1;
                        } else if($rand === 1){
                            $vector["x"] = 1;
                            $vector["y"] = 0;
                        } else if($rand === 2){
                            $vector["x"] = 0;
                            $vector["y"] = -1;
                        } else if($rand === 3){
                            $vector["x"] = -1;
                            $vector["y"] = 0;
                        }
                        foreach($ws_worker->connections as $connection)
                        {
                            $connection->send(json_encode(array("type" => "gameStart", "players" => $players)));
                        }
                    }
                }
            }
        } else if($payload->type === "spectate"){
            $connection->send(json_encode(array("type" => "spectate")));
            $spectators[$spectatorOrder] = array("id" => $connection->id);
            $spectatorOrder++;
        }
    };

    // Emitted when connection closed
    $ws_worker->onClose = function($connection) use ($ws_worker, &$players, &$activeConnections,&$spectators,&$spectatorOrder)
    {
        for($i = 0; $i < count($players);++$i){
            if($players[$i]["id"] === $connection->id){
                unset($players[$i]);
                $players = array_values($players);
            }
        }

        for($i = 0; $i < $spectatorOrder;++$i){
            if($spectators[$i]["id"] === $connection->id){
                unset($spectators[$i]);
                $spectators = array_values($spectators);
                $spectatorOrder--;
            }
        }
        echo "Player left for connection ID " . $connection->id . "\n";
        $activeConnections--;
        foreach($ws_worker->connections as $connection)
        {
            $connection->send(json_encode(array("type" => "updateActiveConnections", "activeConnections" => $activeConnections)));
        }

    };
};
// Run worker
Worker::runAll();


?>
