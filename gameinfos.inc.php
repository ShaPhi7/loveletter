<?php

$gameinfos = array( 

// Game publisher
'publisher' => 'Alderac Entertainment Group',                     

// Url of game publisher website
'publisher_website' => 'https://www.alderac.com/',   

// Board Game Geek ID of the publisher
'publisher_bgg_id' => 396,

// Board game geek if of the game
'bgg_id' => 129622,


// Players configuration that can be played (ex: 2 to 4 players)
'players' => array( 2,3,4,5,6 ),    

// Suggest players to play with this number of players. Must be null if there is no such advice, or if there is only one possible player configuration.
'suggest_player_number' => 4,

// Discourage players to play with these numbers of players. Must be null if there is no such advice.
'not_recommend_player_number' => null,

// Estimated game duration, in minutes (used only for the launch, afterward the real duration is computed)
'estimated_duration' => 15,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = fast)
'fast_additional_time' => 30,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = medium)
'medium_additional_time' => 40,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = slow)
'slow_additional_time' => 50,           

// If you are using a tie breaker in your game (using "player_score_aux"), you must describe here
// the formula used to compute "player_score_aux". This description will be used as a tooltip to explain
// the tie breaker to the players.
// Note: if you are NOT using any tie breaker, leave the empty string.
//
// Example: 'tie_breaker_description' => totranslate( "Number of remaining cards in hand" ),
'tie_breaker_description' => "",

// Game is "beta". A game MUST set is_beta=1 when published on BGA for the first time, and must remains like this until all bugs are fixed.
'is_beta' => 1,                     

// Is this game cooperative (all players wins together or loose together)
'is_coop' => 0,

// Colors attributed to players
'player_colors' => array( "ff0000", "008000", "0000ff", "ffa500", "00ffff", "ff00ff", "ffffff", "000000"  ),

// Favorite colors support : if set to "true", support attribution of favorite colors based on player's preferences (see reattributeColorsBasedOnPreferences PHP method)
'favorite_colors_support' => true,

// Game interface width range (pixels)
// Note: game interface = space on the left side, without the column on the right
'game_interface_width' => array(

    // Minimum width
    //  default: 740
    //  maximum possible value: 740 (ie: your game interface should fit with a 740px width (correspond to a 1024px screen)
    //  minimum possible value: 320 (the lowest value you specify, the better the display is on mobile)
    'min' => 740,

    // Maximum width
    //  default: null (ie: no limit, the game interface is as big as the player's screen allows it).
    //  maximum possible value: unlimited
    //  minimum possible value: 740
    'max' => null
),
);
