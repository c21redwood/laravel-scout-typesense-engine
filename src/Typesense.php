<?php
namespace Redwood\LaravelTypesense;

use Redwood\LaravelTypesense\Interfaces\TypesenseSearch;
use Redwood\Typesence\Client;
use Redwood\Typesence\Document;
use Redwood\Typesence\Collection;
use GuzzleHttp\Exception\GuzzleException;
use Redwood\Typesence\Exceptions\ObjectNotFound;
use Redwood\Typesence\Exceptions\TypesenseClientError;

class Typesense
{

  /**
   * @var \Redwood\Typesence\Client
   */
  private $client;

  /**
   * Typesense constructor.
   *
   * @param   \Redwood\Typesence\Client  $client
   */
  public function __construct(Client $client)
  {
    $this->client = $client;
  }

  /**
   * @return \Redwood\Typesence\Client
   */
  public function getClient(): Client
  {
    return $this->client;
  }

  /**
   * @param   \Illuminate\Database\Eloquent\Model|\Redwood\LaravelTypesense\Interfaces\TypesenseSearch  $model
   *
   * @return \Redwood\Typesence\Collection
   * @throws \Redwood\Typesence\Exceptions\TypesenseClientError
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function createCollectionFromModel($model): Collection
  {
    if (!$model instanceof TypesenseSearch) {
      throw new \InvalidArgumentException(
        sprintf(
          "The given model, argument [0], is not of type %s: %s",
          TypesenseSearch::class,
          get_class($model)
        )
      );
    }

    $schema = $model->getCollectionSchema();
    $indexName = data_get($schema, 'name') ?: $model->searchableAs();

    $index = $this->client->getCollections()->{$indexName};
    try {
      $index->retrieve();

      return $index;
    } catch (ObjectNotFound $exception) {
      $this->client->getCollections()->create(
        $model->getCollectionSchema()
      );

      return $this->client->getCollections()->{$indexName};
    } catch (TypesenseClientError $exception) {
      throw $exception;
    }
  }

  /**
   * @param   \Illuminate\Database\Eloquent\Model  $model
   *
   * @return \Redwood\Typesence\Collection
   * @throws \Redwood\Typesence\Exceptions\TypesenseClientError
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getCollectionIndex($model): Collection
  {
    return $this->createCollectionFromModel($model);
  }

  /**
   * @param   \Redwood\Typesence\Collection  $collectionIndex
   * @param                                   $array
   *
   * @throws \Redwood\Typesence\Exceptions\TypesenseClientError
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function upsertDocument(Collection $collectionIndex, $array): void
  {
    /**
     * @var $document Document
     */
    $document = $collectionIndex->getDocuments()[$array['id']];

    $action = 'updating';
    try {
      try {
        $document->retrieve();
        $document->delete();
        $collectionIndex->getDocuments()->create($array);
      } catch (ObjectNotFound $e) {
        $action = 'creating';
        $collectionIndex->getDocuments()->create($array);
      }
    } catch (\Throwable $e) {
      throw new \Exception(
        sprintf(
          "%s error occurred while %s object #%s in %s",
          basename(get_class($e)),
          $action,
          data_get($array, 'id'),
          $collectionIndex->endPointPath()
        ),
        $e->getCode(),
        $e
      );
    }
  }

  /**
   * @param   \Redwood\Typesence\Collection  $collectionIndex
   * @param                                   $modelId
   */
  public function deleteDocument(Collection $collectionIndex, $modelId): void
  {
    /**
     * @var $document Document
     */
    $document = $collectionIndex->getDocuments()[(string)$modelId];
    try {
      $document->delete();
    } catch (TypesenseClientError $e) {
    } catch (GuzzleException $e) {
    }
  }

}