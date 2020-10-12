<?php
namespace Redwood\LaravelTypesense;

use Illuminate\Support\Facades\Facade;

class TypesenseFacade extends Facade
{

  public static function getFacadeAccessor()
  {
    return 'typesense';
  }

}