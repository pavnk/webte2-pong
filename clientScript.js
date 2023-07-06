const loginScreen = document.getElementById("login-screen");
const gameScreen = document.getElementById("game-screen");
const playerNameInput = document.getElementById("player-name-input");
const joinButton = document.getElementById("join-button");
const spectateButton = document.getElementById("spectate-button");
const leaveButton = document.getElementById("leave-button");
const startGameButton = document.getElementById("start-game-button");
document.body.style.backgroundColor = "gray";

document.body.style.display = "flex";
document.body.style.textAlign = "center";
loginScreen.style.textAlign = "center";
gameScreen.style.textAlign = "center";

// Hide the game screen initially
gameScreen.style.display = "none";
const textDiv = document.createElement("div");
gameScreen.appendChild(textDiv);

const ws = new WebSocket("wss://site179.webte.fei.stuba.sk:9000");
const canvas = document.createElement('canvas');
canvas.width = 600;
canvas.height = 600;

const ctx = canvas.getContext('2d');
//ctx.fillStyle = 'red';
//ctx.fillRect(0, 0,);
gameScreen.appendChild(canvas);

const p = document.createElement("p");
const textNode = document.createTextNode('Active connections: ');

const span = document.createElement('span');
span.setAttribute('id', 'active-count');
span.textContent = '0';

p.appendChild(textNode);
p.appendChild(span);

loginScreen.appendChild(p);

const b = document.createElement("p");
const textNode2 = document.createTextNode('Total bounces: ');

const span2 = document.createElement('span');
span2.setAttribute('id', 'bounce-count');
span2.textContent = '0';

b.appendChild(textNode2);
b.appendChild(span2);

gameScreen.appendChild(b);

let x = 0;
let y = 0;
let playerName;
let playerId, playerOrder;
let gameStarted=false;
let spectator=false;
let alive = true;
let activeConnections = 0;
let bounces = 0;


window.addEventListener("keydown", (event) => {
    if(!spectator){
        if(alive){
            if(playerOrder === 1 || playerOrder === 2){
                if (event.key === "ArrowUp") {
                    y -= 5;
                    ws.send(JSON.stringify({ type: "updateCoordinates", x: x, y: y, order: playerOrder }));
                } else if (event.key === "ArrowDown") {
                    y += 5;
                    ws.send(JSON.stringify({ type: "updateCoordinates", x: x, y: y, order: playerOrder }));
                }
            } else if(playerOrder === 3 || playerOrder === 4){
                if (event.key === "ArrowLeft") {
                    x -= 5;
                    ws.send(JSON.stringify({ type: "updateCoordinates", x: x, y: y, order: playerOrder }));
                } else if (event.key === "ArrowRight") {
                    x += 5;
                    ws.send(JSON.stringify({ type: "updateCoordinates", x: x, y: y, order: playerOrder }));
                }
            }
        }
    }
});

joinButton.addEventListener("click", () => {
    // Get the player name from the input field

    playerName = playerNameInput.value;

    if (playerName.trim() !== '') {
        // Send a message to the server to join the game
        ws.send(JSON.stringify({ type: "join", playerName: playerName }));

        // Hide the login screen and show the game screen
        loginScreen.style.display = "none";
        gameScreen.style.display = "block";
    } else {
        window.alert('Please enter a valid player name.');
        return;
    }
});

function checkPlayerCount(players){
    let playerCount = 0;
    for(let i = 0; i < players.length; i++){
        playerCount++;
    }
    return playerCount;
}

function drawGame(players, ball){
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    let playerCount = checkPlayerCount(players);
    //draw static objects
    ctx.fillStyle = 'black';
    ctx.fillRect(0,0,50,50);
    ctx.fillRect(550,0,50,50);
    ctx.fillRect(0,550,50,50);
    ctx.fillRect(550,550,50,50);
    let paintedFirst = false;
    let paintedSecond = false;
    let paintedThird = false;
    let paintedFourth = false;

    ctx.fillRect(ball.x,ball.y,30,30);
    for(let i=0; i<playerCount;i++){
        //update for first player
        if(players[i].playerOrder === 1){
            ctx.fillStyle = 'green';
            ctx.fillRect(players[i].x,players[i].y,50,100);
            ctx.fillStyle = 'black';
            ctx.fillRect(0,50,50,50);
            ctx.fillRect(0,500,50,50);

            ctx.font = '20px Arial';
            ctx.fillStyle = 'white';
            ctx.fillText(players[i].health, players[i].x+20,players[i].y+55);
            paintedFirst = true;
        }
        if(players[i].playerOrder === 2){
            ctx.fillStyle = 'red';
            ctx.fillRect(players[i].x,players[i].y,50,100);
            ctx.fillStyle = 'black';
            ctx.fillRect(550,50,50,50);
            ctx.fillRect(550,500,50,50);
            ctx.font = '20px Arial';
            ctx.fillStyle = 'white';
            ctx.fillText(players[i].health, players[i].x+20,players[i].y+55);
            paintedSecond = true;
        }
        if(players[i].playerOrder === 3){
            ctx.fillStyle = 'blue';
            ctx.fillRect(players[i].x,players[i].y,100,50);
            ctx.fillStyle = 'black';
            ctx.fillRect(50,0,50,50);
            ctx.fillRect(500,0,50,50);
            ctx.font = '20px Arial';
            ctx.fillStyle = 'white';
            ctx.fillText(players[i].health, players[i].x+45,players[i].y+30);
            paintedThird = true;
        }
        if(players[i].playerOrder === 4){
            ctx.fillStyle = 'purple';
            ctx.fillRect(players[i].x,players[i].y,100,50);
            ctx.fillStyle = 'black';
            ctx.fillRect(50,550,50,50);
            ctx.fillRect(500,550,50,50);
            ctx.font = '20px Arial';
            ctx.fillStyle = 'white';
            ctx.fillText(players[i].health, players[i].x+45,players[i].y+30);
            paintedFourth = true;
        }
    }
    if(!paintedFirst){
        ctx.fillStyle = 'black';
        ctx.fillRect(0,50,50,500);
        paintedFirst = false;
    }
    if(!paintedSecond){
        ctx.fillStyle = 'black';
        ctx.fillRect(550,50,50,500);
        paintedSecond = false;
    }
    if(!paintedThird){
        ctx.fillStyle = 'black';
        ctx.fillRect(50,0,500,50);
        paintedThird = false;
    }
    if(!paintedFourth){
        ctx.fillStyle = 'black';
        ctx.fillRect(50,550,500,50);
        paintedFourth = false;
    }
}

function gameStarting(){
    gameStarted = true;
    ws.send(JSON.stringify({ type: "start" }));
}
function displayPlayerHud(players){
    for(let i =0; i<players.length;++i){
        if(players[i].playerOrder === 1){
            const firstPlayer = document.createElement("p");
            const text = document.createTextNode(players[i].playerName + " has color: GREEN");
            firstPlayer.appendChild(text);
            textDiv.appendChild(firstPlayer);
        }
        if(players[i].playerOrder === 2){
            const secondPlayer = document.createElement("p");
            const text = document.createTextNode(players[i].playerName + " has color: RED");
            secondPlayer.appendChild(text);
            textDiv.appendChild(secondPlayer);
        }
        if(players[i].playerOrder === 3){
            const thirdPlayer = document.createElement("p");
            const text = document.createTextNode(players[i].playerName + " has color: BLUE");
            thirdPlayer.appendChild(text);
            textDiv.appendChild(thirdPlayer);
        }
        if(players[i].playerOrder === 4){
            const fourthPlayer = document.createElement("p");
            const text = document.createTextNode(players[i].playerName + " has color: PURPLE");
            fourthPlayer.appendChild(text);
            textDiv.appendChild(fourthPlayer);
        }
    }
}

// Handle messages received from the server
ws.addEventListener("message", (event) => {
    const message = JSON.parse(event.data);
    if (message.type === "gameUpdate") {

        if(gameStarted){
            if(alive){
                if(!spectator) {
                    x = message.players[playerOrder - 1].x;
                    y = message.players[playerOrder - 1].y;
                    if (message.players[playerOrder - 1].health === null) {
                        alive = false;
                    }
                }
            }
        }
        drawGame(message.players, message.ball);
    }
    if (message.type === "joinSuccessful") {
        // Enable the start game button if this is the first player to join
        startGameButton.disabled = false;

    }
    if(message.type === "updateCoordinates"){
        x = message.x;
        y = message.y;
    }
    if(message.type === "initializeCoordinates"){
        x = message.x;
        y = message.y;
        playerOrder = message.playerOrder;
    }
    if(message.type === "playerId"){
        playerId = message.playerId;
        playerOrder = message.playerOrder;
    }
    if(message.type === "gameStart"){
        startGameButton.disabled = true;
        displayPlayerHud(message.players);
        gameStarting();
    }
    if(message.type === "spectate"){
        spectator = true;
    }
    if(message.type === "updateActiveConnections"){
        activeConnections = message.activeConnections;
        const span = document.querySelector("#active-count");
        span.textContent = activeConnections.toString();
    }
    if(message.type === "bounceUpdate"){
        bounces = message.bounces;
        const span = document.querySelector("#bounce-count");
        span.textContent = bounces.toString();
    }
    if(message.type === "died"){
        if(message.order === playerOrder-1){
            alive = false;
        }
    }
});

// Handle the start game button click
startGameButton.addEventListener("click", () => {
    // Send a message to the server to start the game
    ws.send(JSON.stringify({ type: "startGame", order: playerOrder }));

});

spectateButton.addEventListener("click", () => {
    // Send a message to the server to join the game as a spectator
    ws.send(JSON.stringify({ type: "spectate" }));

    // Hide the login screen and show the game screen
    loginScreen.style.display = "none";
    gameScreen.style.display = "block";
});

leaveButton.addEventListener("click", () => {
    // Send a message to the server to leave the game
    ws.send(JSON.stringify({ type: "leave" }));

    // Hide the game screen and show the login screen
    gameScreen.style.display = "none";
    loginScreen.style.display = "block";

    // Close the WebSocket connection
    ws.close();
    ws = new WebSocket("wss://site179.webte.fei.stuba.sk:9000");
});