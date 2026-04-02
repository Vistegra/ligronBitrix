<?php

namespace CustomMode;

trait LocalLoad
{
  static function initCalc()
  {
    return json_decode(file_get_contents(STORE_DATA . 'calcInit.json'), true);
  }
  static function loadMessages()
  {
    return json_decode(file_get_contents(STORE_DATA . 'messages.json'), true);
  }
  static function loadService()
  {
    return json_decode(file_get_contents(STORE_DATA . 'service.json'), true);
  }
  static function loadSkirting()
  {
    return json_decode(file_get_contents(STORE_DATA . 'skirting.json'), true);
  }
  static function loadSkirtingPlug()
  {
    return json_decode(file_get_contents(STORE_DATA . 'skirtingPlug.json'), true);
  }
  static function loadEdgeProfile()
  {
    return json_decode(file_get_contents(STORE_DATA . 'edgeProfile.json'), true);
  }
  static function loadSinks()
  {
    return json_decode(file_get_contents(STORE_DATA . 'sinkData.json'), true);
  }
  static function loadFaucet()
  {
    return json_decode(file_get_contents(STORE_DATA . 'faucetData.json'), true);
  }
  static function loadComponents()
  {
    return json_decode(file_get_contents(STORE_DATA . 'componentsData.json'), true);
  }

  /** Позже добавить загрузку пользователей и дилеров */
}
