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
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
	"ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.loveletter", ebg.core.gamegui, {
        constructor: function(){
            console.log('loveletter constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            this.playerHand = null;
			
			this.deck = null;
			this.opponentHands = {};
			this.discards = {};
			this.discussionTimeout = {};
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
setup: function (gamedatas) {
  this.lvtPlayers = {};

  const playerIds = Object.keys(gamedatas.players);
  const totalPlayers = playerIds.length;
  const localPlayerId = this.player_id;

  // Reorder player IDs so that local player is always first (bottom)
  const rotatedPlayerIds = [...playerIds];
  while (rotatedPlayerIds[0] !== String(localPlayerId)) {
    rotatedPlayerIds.push(rotatedPlayerIds.shift());
  }

  setTimeout(() => {
    // Skull-style: use viewport dimensions directly
    const centerX = window.innerWidth / 2;
    const centerY = window.innerHeight / 2;
    const radius = 300;

    rotatedPlayerIds.forEach((player_id, index) => {
      const player = gamedatas.players[player_id];

      const angle = (2 * Math.PI * index) / totalPlayers - Math.PI / 2;
      const x = centerX + radius * Math.cos(angle) - 90;
      const y = centerY + radius * Math.sin(angle) - 60;

      const html = `
        <div id="lvt-playertable-${player_id}" class="lvt-playertable" style="left:${x}px; top:${y}px;">
          <div class="lvt-player-name" style="color:#${player.color}">
            ${player.name}
          </div>
        </div>`;

      dojo.place(html, "lvt-playertables");

      this.lvtPlayers[player_id] = {
        id: player_id,
        node: document.getElementById(`lvt-playertable-${player_id}`)
      };
    });
  }, 0);
}


   });             
});
