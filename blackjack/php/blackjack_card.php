<?php
include_once("blackjack_definitions.php");

// Sleep to slow the response speed of the server down
// This makes it look like the cards are really distributed
usleep(750000);

// Proccess the request and create the response object
$response = processRequest();

// Return the response in JSON format
echo json_encode($response);


// -------------------- Functions --------------------
function processRequest()
{
    // Check whether all parameters were received
    if (!array_key_exists('dealer_cards', $_GET) || !array_key_exists('player_cards', $_GET) || !array_key_exists('card_for', $_GET)) {
        return createErrorResponse(
            "Wrong usage: Not all expected parameters have been received by the server "
            . "(expected parameters: 'dealer_cards', 'player_cards' and 'card_for')!"
        );
    }

    // As all parameters were received, we can decode and store them
    $dealer_card_ids = json_decode($_GET['dealer_cards']);
    $player_card_ids = json_decode($_GET['player_cards']);
    $card_for = $_GET['card_for'];

    // If the card ids are invalid
    if (!is_array($dealer_card_ids) || !all_valid($dealer_card_ids) || !is_array($player_card_ids) || !all_valid($player_card_ids)) {
        return createErrorResponse("Wrong usage: Card IDs are not in an array or contain invalid values!");
    }

    // If the card_for parameter is invalid
    if ($card_for != 'player' && $card_for != 'dealer') {
        return createErrorResponse("Wrong usage: 'card_for' is invalid (possible values: 'player', 'dealer')!");
    }

    // Check the current state of the game
    $state = check_state($dealer_card_ids, $player_card_ids);

    // If the game is over, the client can't get a new card
    if ($state != GameState::GAME_NOT_OVER) {
        return createErrorResponse("Wrong usage: You can't take a new card when the game is already over!");
    }

    // Get next card for player or for dealer
    if ($card_for == 'player') {
        $player_card_ids[] = get_next_card($dealer_card_ids, $player_card_ids);
    }
    else if ($card_for == 'dealer') {
        $dealer_card_ids[] = get_next_card($dealer_card_ids, $player_card_ids);
    }

    // Check the current state of the game after getting the new card
    $state = check_state($dealer_card_ids, $player_card_ids);

    switch($state) {
        case GameState::BLACK_JACK:
            return createGameOverResponse($dealer_card_ids, $player_card_ids, "player", "Black Jack! You win!");

        case GameState::TOO_HIGH_PLAYER:
            return createGameOverResponse($dealer_card_ids, $player_card_ids, "dealer", "Your score is higher than 21. The dealer wins!");

        case GameState::TOO_HIGH_DEALER:
            return createGameOverResponse($dealer_card_ids, $player_card_ids, "player", "The dealer's score is higher than 21. You win!");

        case GameState::WIN_DEALER:
            return createGameOverResponse($dealer_card_ids, $player_card_ids, "dealer", "The dealer has a higher score than you. The dealer wins!");

        case GameState::WIN_PLAYER:
            return createGameOverResponse($dealer_card_ids, $player_card_ids, "player", "You have a higher score than the dealer. You win!");

        case GameState::GAME_NOT_OVER:
        default:
            return createInGameResponse($dealer_card_ids, $player_card_ids);
    }
}

function createErrorResponse($msg)
{
    $response = new stdClass();

    $response->status = "error";
    $response->msg = $msg;

    return $response;
}

function createSuccessResponse($dealer_card_ids, $player_card_ids)
{
    $response = new stdClass();

    $response->status = "okay";
    $response->dealer_cards = $dealer_card_ids;
    $response->player_cards = $player_card_ids;
    $response->dealer_value = get_value($dealer_card_ids);
    $response->player_value = get_value($player_card_ids);

    return $response;
}

function createInGameResponse($dealer_card_ids, $player_card_ids)
{
    $response = createSuccessResponse($dealer_card_ids, $player_card_ids);
    $response->game_state = "not-over";

    return $response;
}

function createGameOverResponse($dealer_card_ids, $player_card_ids, $winner, $msg)
{
    $response = createSuccessResponse($dealer_card_ids, $player_card_ids);
    $response->game_state = "game-over";
    $response->winner = $winner;
    $response->msg = $msg;

    return $response;
}

/**
 * Checks whether all values in the input array are valid card ids.
 * @param $card_ids array objects
 * @return bool
 */
function all_valid($card_ids)
{
    global $card_deck;

    foreach ($card_ids as $card_id) {
        if (!array_key_exists($card_id, $card_deck)) {
            return false;
        }
    }

    return true;
}

/**
 * Returns a random new card from the card deck
 * @param $dealer_card_ids The IDs of cards, which where already given to the dealer.
 * @param $player_card_ids The IDs of cards, which where already given to the player.
 * @return mixed
 */
function get_next_card($dealer_card_ids, $player_card_ids)
{
    global $card_deck;

    $available_card_ids = array();
    foreach ($card_deck as $card) {
        if (!in_array($card->id, $dealer_card_ids) && !in_array($card->id, $player_card_ids)) {
            $available_card_ids[] = $card->id;
        }
    }

    if (sizeof($available_card_ids) <= 0) {
        return null;
    }

    $random_index = rand(0, sizeof($available_card_ids) - 1);
    $next_card_id = $available_card_ids[$random_index];

    return $next_card_id;
}

/**
 * Checks the state of the game.
 * @param $dealer_card_ids The IDs of cards, which where already given to the dealer.
 * @param $player_card_ids The IDs of cards, which where already given to the player.
 * @return int State the game is in (GameState constants).
 */
function check_state($dealer_card_ids, $player_card_ids)
{
    if (sizeof($player_card_ids) == 2 && get_value($player_card_ids) == 21) {
        return GameState::BLACK_JACK;
    }
    if (get_value($player_card_ids) > 21) {
        return GameState::TOO_HIGH_PLAYER;
    }
    if (get_value($dealer_card_ids) > 21) {
        return GameState::TOO_HIGH_DEALER;
    }

    if (get_value($dealer_card_ids) <= 16) {
        return GameState::GAME_NOT_OVER;
    }

    if (get_value($dealer_card_ids) >= get_value($player_card_ids)) {
        return GameState::WIN_DEALER;
    }
    else {
        return GameState::WIN_PLAYER;
    }
}

/**
 * Calculates the total value of the given cards.
 * @param $card_ids The IDs of cards to calculate the value for.
 * @return int The total value of the cards.
 */
function get_value($card_ids)
{
    global $card_deck;

    $countAces = 0;
    $totalValue = 0;

    for($i = 0; $i < sizeof($card_ids); $i++) {
        $card = $card_deck[$card_ids[$i]];

        $totalValue += $card->getValue();

        if ($card->rank->rank == Rank::ACE) {
            $countAces++;
        }
    }

    // If the value is higher than 21 but the cards contain an ace, reduce the total value
    while($totalValue > 21 && $countAces > 0) {
        $totalValue -= 10;
        $countAces--;
    }

    return $totalValue;
}

