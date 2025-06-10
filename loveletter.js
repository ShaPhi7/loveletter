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

            dojo.place('lvt-table-center', 'lvt-playertables');

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

            stackDeckCards();
            //TODO - put in proper place, here for testing only.
            // Add shuffle animation
            shuffleDeckAnimation({
                containerId: 'deck_1',
                cardCount: 7,
                cardClass: 'lvt-card-back',
            }).then(() => {
                console.log('Deck shuffled!');
            });
        }
    });

function shuffleDeckAnimation({
  containerId = 'deck_1',
  cardCount = 21,
  cardClass = 'lvt-card-back',
  spreadRadius = 80,
  hopRadius = 60,
  duration = 250,        // each stage lasts 250ms (was 150)
  delayStep = 60,        // cards stagger every 60ms (was 30)
  pauseAfter = 300,      // pause before cleanup
} = {}) {
  return new Promise((resolve) => {
    const container = document.getElementById(containerId);
    if (!container) {
      console.warn('Deck container not found:', containerId);
      resolve(false);
      return;
    }

    const elements = [];

    for (let i = 0; i < cardCount; i++) {
      const card = document.createElement('div');
      card.classList.add(cardClass);
      card.style.position = 'absolute';
      card.style.top = '0';
      card.style.left = '0';
      card.style.transition = 'transform 0ms';
      card.style.zIndex = `${i}`;
      container.appendChild(card);
      elements.push(card);
    }

    elements.forEach((el, i) => {
      const angle = Math.random() * 2 * Math.PI;
      const dist = spreadRadius + Math.random() * 20;
      const x1 = Math.cos(angle) * dist;
      const y1 = Math.sin(angle) * dist;

      setTimeout(() => {
        el.style.transition = `transform ${duration}ms ease-out`;
        el.style.transform = `translate(${x1}px, ${y1}px)`;

        setTimeout(() => {
          const hopAngle = Math.random() * 2 * Math.PI;
          const x2 = Math.cos(hopAngle) * hopRadius;
          const y2 = Math.sin(hopAngle) * hopRadius;

          el.style.transition = `transform ${duration}ms ease-in-out`;
          el.style.transform = `translate(${x1 + x2}px, ${y1 + y2}px)`;

          setTimeout(() => {
            el.style.transition = `transform ${duration}ms ease-in`;
            el.style.transform = `translate(0px, 0px)`;

            if (i === elements.length - 1) {
              setTimeout(() => {
                elements.forEach(e => e.remove());
                resolve(true);
              }, duration + pauseAfter);
            }
          }, duration);
        }, duration);
      }, i * delayStep);
    });
  });
}

function stackDeckCards(containerId = 'lvt-table-center', offsetX = 1, offsetY = 1) {
  const cards = document.querySelectorAll(`#${containerId} .lvt-card-back`);
  cards.forEach((card, i) => {
    card.style.right = `${i * offsetY}px`;
    card.style.bottom = `${i * offsetX}px`;
    card.style.zIndex = i;
  });
  console.log('Stacked deck cards in', containerId, 'Count:', cards.length);
}

});
