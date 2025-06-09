/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * loveletter implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * loveletter.js
 *
 * loveletter user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.loveletter", ebg.core.gamegui, {
        constructor: function () {
            console.log('loveletter constructor');

            this.playerHand = null;
            this.deck = null;
            this.opponentHands = {};
            this.discards = {};
            this.discussionTimeout = {};
        },

        setup(gamedatas) {
            console.log("Love Letter setup", gamedatas);

            this.lvtPlayers = {};
            const playerIds = Object.keys(gamedatas.players);
            const totalPlayers = playerIds.length;
            const localPlayerId = this.player_id;

            // Rotate so local player is first
            const rotatedPlayerIds = [...playerIds];
            while (rotatedPlayerIds[0] !== String(localPlayerId)) {
                rotatedPlayerIds.push(rotatedPlayerIds.shift());
            }

            const radius = 300;

            rotatedPlayerIds.forEach((player_id, index) => {
                const player = gamedatas.players[player_id];
                const angle = (2 * Math.PI * index) / totalPlayers;

                const x = Math.cos(angle) * radius;
                const y = Math.sin(angle) * radius;

                const html = `
                    <div id="lvt-playertable-${player_id}" class="lvt-playertable"
                         style="left: calc(50% + ${x}px); top: calc(50% + ${y}px); transform: translate(-50%, -50%)">
                        <div class="lvt-player-name" style="color:#${player.color}">${player.name}</div>
                    </div>`;

                dojo.place(html, "lvt-playertables");

                this.lvtPlayers[player_id] = {
                    id: player_id,
                    node: document.getElementById(`lvt-playertable-${player_id}`)
                };
            });
        }
    });
});
