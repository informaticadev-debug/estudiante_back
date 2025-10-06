<?php

/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

/**
 * Service definition for MapsEngine (v1).
 *
 * <p>
 * The Google Maps Engine API allows developers to store and query geospatial
 * vector and raster data.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/maps-engine/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_MapsEngine extends Google_Service
{
/** View and manage your Google My Maps data. */
const MAPSENGINE = "https://www.googleapis.com/auth/mapsengine";
/** View your Google My Maps data. */
const MAPSENGINE_READONLY = "https://www.googleapis.com/auth/mapsengine.readonly";

public $assets;
public $assets_parents;
public $assets_permissions;
public $layers;
public $layers_parents;
public $layers_permissions;
public $maps;
public $maps_permissions;
public $projects;
public $projects_icons;
public $rasterCollections;
public $rasterCollections_parents;
public $rasterCollections_permissions;
public $rasterCollections_rasters;
public $rasters;
public $rasters_files;
public $rasters_parents;
public $rasters_permissions;
public $tables;
public $tables_features;
public $tables_files;
public $tables_parents;
public $tables_permissions;


/**
 * Constructs the internal representation of the MapsEngine service.
 *
 * @param Google_Client $client
 */
public function __construct(Google_Client $client)
{
parent::__construct($client);
$this->rootUrl = 'https://www.googleapis.com/';
$this->servicePath = 'mapsengine/v1/';
$this->version = 'v1';
$this->serviceName = 'mapsengine';

$this->assets = new Google_Service_MapsEngine_Assets_Resource(
$this,
 $this->serviceName,
 'assets',
 array(
'methods' => array(
'get' => array(
'path' => 'assets/{id}',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'assets',
 'httpMethod' => 'GET',
 'parameters' => array(
'modifiedAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'tags' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'projectId' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'search' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'creatorEmail' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'bbox' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'modifiedBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'role' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'type' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ),
 )
)
);
$this->assets_parents = new Google_Service_MapsEngine_AssetsParents_Resource(
$this,
 $this->serviceName,
 'parents',
 array(
'methods' => array(
'list' => array(
'path' => 'assets/{id}/parents',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 ),
 ),
 )
)
);
$this->assets_permissions = new Google_Service_MapsEngine_AssetsPermissions_Resource(
$this,
 $this->serviceName,
 'permissions',
 array(
'methods' => array(
'list' => array(
'path' => 'assets/{id}/permissions',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ),
 )
)
);
$this->layers = new Google_Service_MapsEngine_Layers_Resource(
$this,
 $this->serviceName,
 'layers',
 array(
'methods' => array(
'cancelProcessing' => array(
'path' => 'layers/{id}/cancelProcessing',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'create' => array(
'path' => 'layers',
 'httpMethod' => 'POST',
 'parameters' => array(
'process' => array(
'location' => 'query',
 'type' => 'boolean',
 ),
 ),
 ), 'delete' => array(
'path' => 'layers/{id}',
 'httpMethod' => 'DELETE',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'get' => array(
'path' => 'layers/{id}',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'version' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ), 'getPublished' => array(
'path' => 'layers/{id}/published',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'layers',
 'httpMethod' => 'GET',
 'parameters' => array(
'modifiedAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'processingStatus' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'projectId' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'tags' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'search' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'creatorEmail' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'bbox' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'modifiedBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'role' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ), 'listPublished' => array(
'path' => 'layers/published',
 'httpMethod' => 'GET',
 'parameters' => array(
'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 'projectId' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ), 'patch' => array(
'path' => 'layers/{id}',
 'httpMethod' => 'PATCH',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'process' => array(
'path' => 'layers/{id}/process',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'publish' => array(
'path' => 'layers/{id}/publish',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'force' => array(
'location' => 'query',
 'type' => 'boolean',
 ),
 ),
 ), 'unpublish' => array(
'path' => 'layers/{id}/unpublish',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ),
 )
)
);
$this->layers_parents = new Google_Service_MapsEngine_LayersParents_Resource(
$this,
 $this->serviceName,
 'parents',
 array(
'methods' => array(
'list' => array(
'path' => 'layers/{id}/parents',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 ),
 ),
 )
)
);
$this->layers_permissions = new Google_Service_MapsEngine_LayersPermissions_Resource(
$this,
 $this->serviceName,
 'permissions',
 array(
'methods' => array(
'batchDelete' => array(
'path' => 'layers/{id}/permissions/batchDelete',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'batchUpdate' => array(
'path' => 'layers/{id}/permissions/batchUpdate',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'layers/{id}/permissions',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ),
 )
)
);
$this->maps = new Google_Service_MapsEngine_Maps_Resource(
$this,
 $this->serviceName,
 'maps',
 array(
'methods' => array(
'create' => array(
'path' => 'maps',
 'httpMethod' => 'POST',
 'parameters' => array(),
 ), 'delete' => array(
'path' => 'maps/{id}',
 'httpMethod' => 'DELETE',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'get' => array(
'path' => 'maps/{id}',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'version' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ), 'getPublished' => array(
'path' => 'maps/{id}/published',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'maps',
 'httpMethod' => 'GET',
 'parameters' => array(
'modifiedAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'processingStatus' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'projectId' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'tags' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'search' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'creatorEmail' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'bbox' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'modifiedBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'role' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ), 'listPublished' => array(
'path' => 'maps/published',
 'httpMethod' => 'GET',
 'parameters' => array(
'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 'projectId' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ), 'patch' => array(
'path' => 'maps/{id}',
 'httpMethod' => 'PATCH',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'publish' => array(
'path' => 'maps/{id}/publish',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'force' => array(
'location' => 'query',
 'type' => 'boolean',
 ),
 ),
 ), 'unpublish' => array(
'path' => 'maps/{id}/unpublish',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ),
 )
)
);
$this->maps_permissions = new Google_Service_MapsEngine_MapsPermissions_Resource(
$this,
 $this->serviceName,
 'permissions',
 array(
'methods' => array(
'batchDelete' => array(
'path' => 'maps/{id}/permissions/batchDelete',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'batchUpdate' => array(
'path' => 'maps/{id}/permissions/batchUpdate',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'maps/{id}/permissions',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ),
 )
)
);
$this->projects = new Google_Service_MapsEngine_Projects_Resource(
$this,
 $this->serviceName,
 'projects',
 array(
'methods' => array(
'list' => array(
'path' => 'projects',
 'httpMethod' => 'GET',
 'parameters' => array(),
 ),
 )
)
);
$this->projects_icons = new Google_Service_MapsEngine_ProjectsIcons_Resource(
$this,
 $this->serviceName,
 'icons',
 array(
'methods' => array(
'create' => array(
'path' => 'projects/{projectId}/icons',
 'httpMethod' => 'POST',
 'parameters' => array(
'projectId' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'get' => array(
'path' => 'projects/{projectId}/icons/{id}',
 'httpMethod' => 'GET',
 'parameters' => array(
'projectId' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'projects/{projectId}/icons',
 'httpMethod' => 'GET',
 'parameters' => array(
'projectId' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 ),
 ),
 )
)
);
$this->rasterCollections = new Google_Service_MapsEngine_RasterCollections_Resource(
$this,
 $this->serviceName,
 'rasterCollections',
 array(
'methods' => array(
'cancelProcessing' => array(
'path' => 'rasterCollections/{id}/cancelProcessing',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'create' => array(
'path' => 'rasterCollections',
 'httpMethod' => 'POST',
 'parameters' => array(),
 ), 'delete' => array(
'path' => 'rasterCollections/{id}',
 'httpMethod' => 'DELETE',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'get' => array(
'path' => 'rasterCollections/{id}',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'rasterCollections',
 'httpMethod' => 'GET',
 'parameters' => array(
'modifiedAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'processingStatus' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'projectId' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'tags' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'search' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'creatorEmail' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'bbox' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'modifiedBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'role' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ), 'patch' => array(
'path' => 'rasterCollections/{id}',
 'httpMethod' => 'PATCH',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'process' => array(
'path' => 'rasterCollections/{id}/process',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ),
 )
)
);
$this->rasterCollections_parents = new Google_Service_MapsEngine_RasterCollectionsParents_Resource(
$this,
 $this->serviceName,
 'parents',
 array(
'methods' => array(
'list' => array(
'path' => 'rasterCollections/{id}/parents',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 ),
 ),
 )
)
);
$this->rasterCollections_permissions = new Google_Service_MapsEngine_RasterCollectionsPermissions_Resource(
$this,
 $this->serviceName,
 'permissions',
 array(
'methods' => array(
'batchDelete' => array(
'path' => 'rasterCollections/{id}/permissions/batchDelete',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'batchUpdate' => array(
'path' => 'rasterCollections/{id}/permissions/batchUpdate',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'rasterCollections/{id}/permissions',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ),
 )
)
);
$this->rasterCollections_rasters = new Google_Service_MapsEngine_RasterCollectionsRasters_Resource(
$this,
 $this->serviceName,
 'rasters',
 array(
'methods' => array(
'batchDelete' => array(
'path' => 'rasterCollections/{id}/rasters/batchDelete',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'batchInsert' => array(
'path' => 'rasterCollections/{id}/rasters/batchInsert',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'rasterCollections/{id}/rasters',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'modifiedAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'tags' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'search' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'creatorEmail' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'bbox' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'modifiedBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'role' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ),
 )
)
);
$this->rasters = new Google_Service_MapsEngine_Rasters_Resource(
$this,
 $this->serviceName,
 'rasters',
 array(
'methods' => array(
'delete' => array(
'path' => 'rasters/{id}',
 'httpMethod' => 'DELETE',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'get' => array(
'path' => 'rasters/{id}',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'rasters',
 'httpMethod' => 'GET',
 'parameters' => array(
'projectId' => array(
'location' => 'query',
 'type' => 'string',
 'required' => true,
 ),
 'modifiedAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'processingStatus' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'tags' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'search' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'creatorEmail' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'bbox' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'modifiedBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'role' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ), 'patch' => array(
'path' => 'rasters/{id}',
 'httpMethod' => 'PATCH',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'process' => array(
'path' => 'rasters/{id}/process',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'upload' => array(
'path' => 'rasters/upload',
 'httpMethod' => 'POST',
 'parameters' => array(),
 ),
 )
)
);
$this->rasters_files = new Google_Service_MapsEngine_RastersFiles_Resource(
$this,
 $this->serviceName,
 'files',
 array(
'methods' => array(
'insert' => array(
'path' => 'rasters/{id}/files',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'filename' => array(
'location' => 'query',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ),
 )
)
);
$this->rasters_parents = new Google_Service_MapsEngine_RastersParents_Resource(
$this,
 $this->serviceName,
 'parents',
 array(
'methods' => array(
'list' => array(
'path' => 'rasters/{id}/parents',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 ),
 ),
 )
)
);
$this->rasters_permissions = new Google_Service_MapsEngine_RastersPermissions_Resource(
$this,
 $this->serviceName,
 'permissions',
 array(
'methods' => array(
'batchDelete' => array(
'path' => 'rasters/{id}/permissions/batchDelete',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'batchUpdate' => array(
'path' => 'rasters/{id}/permissions/batchUpdate',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'rasters/{id}/permissions',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ),
 )
)
);
$this->tables = new Google_Service_MapsEngine_Tables_Resource(
$this,
 $this->serviceName,
 'tables',
 array(
'methods' => array(
'create' => array(
'path' => 'tables',
 'httpMethod' => 'POST',
 'parameters' => array(),
 ), 'delete' => array(
'path' => 'tables/{id}',
 'httpMethod' => 'DELETE',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'get' => array(
'path' => 'tables/{id}',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'version' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ), 'list' => array(
'path' => 'tables',
 'httpMethod' => 'GET',
 'parameters' => array(
'modifiedAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdAfter' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'processingStatus' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'projectId' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'tags' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'search' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'creatorEmail' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'bbox' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'modifiedBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'createdBefore' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'role' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ), 'patch' => array(
'path' => 'tables/{id}',
 'httpMethod' => 'PATCH',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'process' => array(
'path' => 'tables/{id}/process',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'upload' => array(
'path' => 'tables/upload',
 'httpMethod' => 'POST',
 'parameters' => array(),
 ),
 )
)
);
$this->tables_features = new Google_Service_MapsEngine_TablesFeatures_Resource(
$this,
 $this->serviceName,
 'features',
 array(
'methods' => array(
'batchDelete' => array(
'path' => 'tables/{id}/features/batchDelete',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'batchInsert' => array(
'path' => 'tables/{id}/features/batchInsert',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'batchPatch' => array(
'path' => 'tables/{id}/features/batchPatch',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'get' => array(
'path' => 'tables/{tableId}/features/{id}',
 'httpMethod' => 'GET',
 'parameters' => array(
'tableId' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'version' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'select' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ), 'list' => array(
'path' => 'tables/{id}/features',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'orderBy' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'intersects' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'version' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'limit' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 'include' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'where' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'select' => array(
'location' => 'query',
 'type' => 'string',
 ),
 ),
 ),
 )
)
);
$this->tables_files = new Google_Service_MapsEngine_TablesFiles_Resource(
$this,
 $this->serviceName,
 'files',
 array(
'methods' => array(
'insert' => array(
'path' => 'tables/{id}/files',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'filename' => array(
'location' => 'query',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ),
 )
)
);
$this->tables_parents = new Google_Service_MapsEngine_TablesParents_Resource(
$this,
 $this->serviceName,
 'parents',
 array(
'methods' => array(
'list' => array(
'path' => 'tables/{id}/parents',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 'pageToken' => array(
'location' => 'query',
 'type' => 'string',
 ),
 'maxResults' => array(
'location' => 'query',
 'type' => 'integer',
 ),
 ),
 ),
 )
)
);
$this->tables_permissions = new Google_Service_MapsEngine_TablesPermissions_Resource(
$this,
 $this->serviceName,
 'permissions',
 array(
'methods' => array(
'batchDelete' => array(
'path' => 'tables/{id}/permissions/batchDelete',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'batchUpdate' => array(
'path' => 'tables/{id}/permissions/batchUpdate',
 'httpMethod' => 'POST',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ), 'list' => array(
'path' => 'tables/{id}/permissions',
 'httpMethod' => 'GET',
 'parameters' => array(
'id' => array(
'location' => 'path',
 'type' => 'string',
 'required' => true,
 ),
 ),
 ),
 )
)
);
}
}


/**
 * The "assets" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $assets = $mapsengineService->assets;
 *  </code>
 */
class Google_Service_MapsEngine_Assets_Resource extends Google_Service_Resource
{

/**
 * Return metadata for a particular asset. (assets.get)
 *
 * @param string $id The ID of the asset.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_Asset
 */
public function get($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('get', array($params), "Google_Service_MapsEngine_Asset");
}

/**
 * Return all assets readable by the current user. (assets.listAssets)
 *
 * @param array $optParams Optional parameters.
 *
 * @opt_param string modifiedAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or after
 * this time.
 * @opt_param string createdAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or after
 * this time.
 * @opt_param string tags A comma separated list of tags. Returned assets will
 * contain all the tags from the list.
 * @opt_param string projectId The ID of a Maps Engine project, used to filter
 * the response. To list all available projects with their IDs, send a Projects:
 * list request. You can also find your project ID as the value of the
 * DashboardPlace:cid URL parameter when signed in to mapsengine.google.com.
 * @opt_param string search An unstructured search string used to filter the set
 * of results based on asset metadata.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 100.
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string creatorEmail An email address representing a user. Returned
 * assets that have been created by the user associated with the provided email
 * address.
 * @opt_param string bbox A bounding box, expressed as "west,south,east,north".
 * If set, only assets which intersect this bounding box will be returned.
 * @opt_param string modifiedBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or before
 * this time.
 * @opt_param string createdBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or before
 * this time.
 * @opt_param string role The role parameter indicates that the response should
 * only contain assets where the current user has the specified level of access.
 * @opt_param string type A comma separated list of asset types. Returned assets
 * will have one of the types from the provided list. Supported values are
 * 'map', 'layer', 'rasterCollection' and 'table'.
 * @return Google_Service_MapsEngine_AssetsListResponse
 */
public function listAssets($optParams = array())
{
$params = array();
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_AssetsListResponse");
}
}

/**
 * The "parents" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $parents = $mapsengineService->parents;
 *  </code>
 */
class Google_Service_MapsEngine_AssetsParents_Resource extends Google_Service_Resource
{

/**
 * Return all parent ids of the specified asset. (parents.listAssetsParents)
 *
 * @param string $id The ID of the asset whose parents will be listed.
 * @param array $optParams Optional parameters.
 *
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 50.
 * @return Google_Service_MapsEngine_ParentsListResponse
 */
public function listAssetsParents($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_ParentsListResponse");
}
}
/**
 * The "permissions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $permissions = $mapsengineService->permissions;
 *  </code>
 */
class Google_Service_MapsEngine_AssetsPermissions_Resource extends Google_Service_Resource
{

/**
 * Return all of the permissions for the specified asset.
 * (permissions.listAssetsPermissions)
 *
 * @param string $id The ID of the asset whose permissions will be listed.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsListResponse
 */
public function listAssetsPermissions($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_PermissionsListResponse");
}
}

/**
 * The "layers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $layers = $mapsengineService->layers;
 *  </code>
 */
class Google_Service_MapsEngine_Layers_Resource extends Google_Service_Resource
{

/**
 * Cancel processing on a layer asset. (layers.cancelProcessing)
 *
 * @param string $id The ID of the layer.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_ProcessResponse
 */
public function cancelProcessing($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('cancelProcessing', array($params), "Google_Service_MapsEngine_ProcessResponse");
}

/**
 * Create a layer asset. (layers.create)
 *
 * @param Google_Layer $postBody
 * @param array $optParams Optional parameters.
 *
 * @opt_param bool process Whether to queue the created layer for processing.
 * @return Google_Service_MapsEngine_Layer
 */
public function create(Google_Service_MapsEngine_Layer $postBody, $optParams = array())
{
$params = array('postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('create', array($params), "Google_Service_MapsEngine_Layer");
}

/**
 * Delete a layer. (layers.delete)
 *
 * @param string $id The ID of the layer. Only the layer creator or project
 * owner are permitted to delete. If the layer is published, or included in a
 * map, the request will fail. Unpublish the layer, and remove it from all maps
 * prior to deleting.
 * @param array $optParams Optional parameters.
 */
public function delete($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('delete', array($params));
}

/**
 * Return metadata for a particular layer. (layers.get)
 *
 * @param string $id The ID of the layer.
 * @param array $optParams Optional parameters.
 *
 * @opt_param string version Deprecated: The version parameter indicates which
 * version of the layer should be returned. When version is set to published,
 * the published version of the layer will be returned. Please use the
 * layers.getPublished endpoint instead.
 * @return Google_Service_MapsEngine_Layer
 */
public function get($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('get', array($params), "Google_Service_MapsEngine_Layer");
}

/**
 * Return the published metadata for a particular layer. (layers.getPublished)
 *
 * @param string $id The ID of the layer.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PublishedLayer
 */
public function getPublished($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('getPublished', array($params), "Google_Service_MapsEngine_PublishedLayer");
}

/**
 * Return all layers readable by the current user. (layers.listLayers)
 *
 * @param array $optParams Optional parameters.
 *
 * @opt_param string modifiedAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or after
 * this time.
 * @opt_param string createdAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or after
 * this time.
 * @opt_param string processingStatus
 * @opt_param string projectId The ID of a Maps Engine project, used to filter
 * the response. To list all available projects with their IDs, send a Projects:
 * list request. You can also find your project ID as the value of the
 * DashboardPlace:cid URL parameter when signed in to mapsengine.google.com.
 * @opt_param string tags A comma separated list of tags. Returned assets will
 * contain all the tags from the list.
 * @opt_param string search An unstructured search string used to filter the set
 * of results based on asset metadata.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 100.
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string creatorEmail An email address representing a user. Returned
 * assets that have been created by the user associated with the provided email
 * address.
 * @opt_param string bbox A bounding box, expressed as "west,south,east,north".
 * If set, only assets which intersect this bounding box will be returned.
 * @opt_param string modifiedBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or before
 * this time.
 * @opt_param string createdBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or before
 * this time.
 * @opt_param string role The role parameter indicates that the response should
 * only contain assets where the current user has the specified level of access.
 * @return Google_Service_MapsEngine_LayersListResponse
 */
public function listLayers($optParams = array())
{
$params = array();
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_LayersListResponse");
}

/**
 * Return all published layers readable by the current user.
 * (layers.listPublished)
 *
 * @param array $optParams Optional parameters.
 *
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 100.
 * @opt_param string projectId The ID of a Maps Engine project, used to filter
 * the response. To list all available projects with their IDs, send a Projects:
 * list request. You can also find your project ID as the value of the
 * DashboardPlace:cid URL parameter when signed in to mapsengine.google.com.
 * @return Google_Service_MapsEngine_PublishedLayersListResponse
 */
public function listPublished($optParams = array())
{
$params = array();
$params = array_merge($params, $optParams);
return $this->call('listPublished', array($params), "Google_Service_MapsEngine_PublishedLayersListResponse");
}

/**
 * Mutate a layer asset. (layers.patch)
 *
 * @param string $id The ID of the layer.
 * @param Google_Layer $postBody
 * @param array $optParams Optional parameters.
 */
public function patch($id, Google_Service_MapsEngine_Layer $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('patch', array($params));
}

/**
 * Process a layer asset. (layers.process)
 *
 * @param string $id The ID of the layer.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_ProcessResponse
 */
public function process($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('process', array($params), "Google_Service_MapsEngine_ProcessResponse");
}

/**
 * Publish a layer asset. (layers.publish)
 *
 * @param string $id The ID of the layer.
 * @param array $optParams Optional parameters.
 *
 * @opt_param bool force If set to true, the API will allow publication of the
 * layer even if it's out of date. If not true, you'll need to reprocess any
 * out-of-date layer before publishing.
 * @return Google_Service_MapsEngine_PublishResponse
 */
public function publish($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('publish', array($params), "Google_Service_MapsEngine_PublishResponse");
}

/**
 * Unpublish a layer asset. (layers.unpublish)
 *
 * @param string $id The ID of the layer.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PublishResponse
 */
public function unpublish($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('unpublish', array($params), "Google_Service_MapsEngine_PublishResponse");
}
}

/**
 * The "parents" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $parents = $mapsengineService->parents;
 *  </code>
 */
class Google_Service_MapsEngine_LayersParents_Resource extends Google_Service_Resource
{

/**
 * Return all parent ids of the specified layer. (parents.listLayersParents)
 *
 * @param string $id The ID of the layer whose parents will be listed.
 * @param array $optParams Optional parameters.
 *
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 50.
 * @return Google_Service_MapsEngine_ParentsListResponse
 */
public function listLayersParents($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_ParentsListResponse");
}
}
/**
 * The "permissions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $permissions = $mapsengineService->permissions;
 *  </code>
 */
class Google_Service_MapsEngine_LayersPermissions_Resource extends Google_Service_Resource
{

/**
 * Remove permission entries from an already existing asset.
 * (permissions.batchDelete)
 *
 * @param string $id The ID of the asset from which permissions will be removed.
 * @param Google_PermissionsBatchDeleteRequest $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsBatchDeleteResponse
 */
public function batchDelete($id, Google_Service_MapsEngine_PermissionsBatchDeleteRequest $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('batchDelete', array($params), "Google_Service_MapsEngine_PermissionsBatchDeleteResponse");
}

/**
 * Add or update permission entries to an already existing asset.
 *
 * An asset can hold up to 20 different permission entries. Each batchInsert
 * request is atomic. (permissions.batchUpdate)
 *
 * @param string $id The ID of the asset to which permissions will be added.
 * @param Google_PermissionsBatchUpdateRequest $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsBatchUpdateResponse
 */
public function batchUpdate($id, Google_Service_MapsEngine_PermissionsBatchUpdateRequest $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('batchUpdate', array($params), "Google_Service_MapsEngine_PermissionsBatchUpdateResponse");
}

/**
 * Return all of the permissions for the specified asset.
 * (permissions.listLayersPermissions)
 *
 * @param string $id The ID of the asset whose permissions will be listed.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsListResponse
 */
public function listLayersPermissions($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_PermissionsListResponse");
}
}

/**
 * The "maps" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $maps = $mapsengineService->maps;
 *  </code>
 */
class Google_Service_MapsEngine_Maps_Resource extends Google_Service_Resource
{

/**
 * Create a map asset. (maps.create)
 *
 * @param Google_Map $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_Map
 */
public function create(Google_Service_MapsEngine_Map $postBody, $optParams = array())
{
$params = array('postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('create', array($params), "Google_Service_MapsEngine_Map");
}

/**
 * Delete a map. (maps.delete)
 *
 * @param string $id The ID of the map. Only the map creator or project owner
 * are permitted to delete. If the map is published the request will fail.
 * Unpublish the map prior to deleting.
 * @param array $optParams Optional parameters.
 */
public function delete($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('delete', array($params));
}

/**
 * Return metadata for a particular map. (maps.get)
 *
 * @param string $id The ID of the map.
 * @param array $optParams Optional parameters.
 *
 * @opt_param string version Deprecated: The version parameter indicates which
 * version of the map should be returned. When version is set to published, the
 * published version of the map will be returned. Please use the
 * maps.getPublished endpoint instead.
 * @return Google_Service_MapsEngine_Map
 */
public function get($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('get', array($params), "Google_Service_MapsEngine_Map");
}

/**
 * Return the published metadata for a particular map. (maps.getPublished)
 *
 * @param string $id The ID of the map.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PublishedMap
 */
public function getPublished($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('getPublished', array($params), "Google_Service_MapsEngine_PublishedMap");
}

/**
 * Return all maps readable by the current user. (maps.listMaps)
 *
 * @param array $optParams Optional parameters.
 *
 * @opt_param string modifiedAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or after
 * this time.
 * @opt_param string createdAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or after
 * this time.
 * @opt_param string processingStatus
 * @opt_param string projectId The ID of a Maps Engine project, used to filter
 * the response. To list all available projects with their IDs, send a Projects:
 * list request. You can also find your project ID as the value of the
 * DashboardPlace:cid URL parameter when signed in to mapsengine.google.com.
 * @opt_param string tags A comma separated list of tags. Returned assets will
 * contain all the tags from the list.
 * @opt_param string search An unstructured search string used to filter the set
 * of results based on asset metadata.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 100.
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string creatorEmail An email address representing a user. Returned
 * assets that have been created by the user associated with the provided email
 * address.
 * @opt_param string bbox A bounding box, expressed as "west,south,east,north".
 * If set, only assets which intersect this bounding box will be returned.
 * @opt_param string modifiedBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or before
 * this time.
 * @opt_param string createdBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or before
 * this time.
 * @opt_param string role The role parameter indicates that the response should
 * only contain assets where the current user has the specified level of access.
 * @return Google_Service_MapsEngine_MapsListResponse
 */
public function listMaps($optParams = array())
{
$params = array();
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_MapsListResponse");
}

/**
 * Return all published maps readable by the current user. (maps.listPublished)
 *
 * @param array $optParams Optional parameters.
 *
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 100.
 * @opt_param string projectId The ID of a Maps Engine project, used to filter
 * the response. To list all available projects with their IDs, send a Projects:
 * list request. You can also find your project ID as the value of the
 * DashboardPlace:cid URL parameter when signed in to mapsengine.google.com.
 * @return Google_Service_MapsEngine_PublishedMapsListResponse
 */
public function listPublished($optParams = array())
{
$params = array();
$params = array_merge($params, $optParams);
return $this->call('listPublished', array($params), "Google_Service_MapsEngine_PublishedMapsListResponse");
}

/**
 * Mutate a map asset. (maps.patch)
 *
 * @param string $id The ID of the map.
 * @param Google_Map $postBody
 * @param array $optParams Optional parameters.
 */
public function patch($id, Google_Service_MapsEngine_Map $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('patch', array($params));
}

/**
 * Publish a map asset. (maps.publish)
 *
 * @param string $id The ID of the map.
 * @param array $optParams Optional parameters.
 *
 * @opt_param bool force If set to true, the API will allow publication of the
 * map even if it's out of date. If false, the map must have a processingStatus
 * of complete before publishing.
 * @return Google_Service_MapsEngine_PublishResponse
 */
public function publish($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('publish', array($params), "Google_Service_MapsEngine_PublishResponse");
}

/**
 * Unpublish a map asset. (maps.unpublish)
 *
 * @param string $id The ID of the map.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PublishResponse
 */
public function unpublish($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('unpublish', array($params), "Google_Service_MapsEngine_PublishResponse");
}
}

/**
 * The "permissions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $permissions = $mapsengineService->permissions;
 *  </code>
 */
class Google_Service_MapsEngine_MapsPermissions_Resource extends Google_Service_Resource
{

/**
 * Remove permission entries from an already existing asset.
 * (permissions.batchDelete)
 *
 * @param string $id The ID of the asset from which permissions will be removed.
 * @param Google_PermissionsBatchDeleteRequest $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsBatchDeleteResponse
 */
public function batchDelete($id, Google_Service_MapsEngine_PermissionsBatchDeleteRequest $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('batchDelete', array($params), "Google_Service_MapsEngine_PermissionsBatchDeleteResponse");
}

/**
 * Add or update permission entries to an already existing asset.
 *
 * An asset can hold up to 20 different permission entries. Each batchInsert
 * request is atomic. (permissions.batchUpdate)
 *
 * @param string $id The ID of the asset to which permissions will be added.
 * @param Google_PermissionsBatchUpdateRequest $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsBatchUpdateResponse
 */
public function batchUpdate($id, Google_Service_MapsEngine_PermissionsBatchUpdateRequest $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('batchUpdate', array($params), "Google_Service_MapsEngine_PermissionsBatchUpdateResponse");
}

/**
 * Return all of the permissions for the specified asset.
 * (permissions.listMapsPermissions)
 *
 * @param string $id The ID of the asset whose permissions will be listed.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsListResponse
 */
public function listMapsPermissions($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_PermissionsListResponse");
}
}

/**
 * The "projects" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $projects = $mapsengineService->projects;
 *  </code>
 */
class Google_Service_MapsEngine_Projects_Resource extends Google_Service_Resource
{

/**
 * Return all projects readable by the current user. (projects.listProjects)
 *
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_ProjectsListResponse
 */
public function listProjects($optParams = array())
{
$params = array();
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_ProjectsListResponse");
}
}

/**
 * The "icons" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $icons = $mapsengineService->icons;
 *  </code>
 */
class Google_Service_MapsEngine_ProjectsIcons_Resource extends Google_Service_Resource
{

/**
 * Create an icon. (icons.create)
 *
 * @param string $projectId The ID of the project.
 * @param Google_Icon $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_Icon
 */
public function create($projectId, Google_Service_MapsEngine_Icon $postBody, $optParams = array())
{
$params = array('projectId' => $projectId, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('create', array($params), "Google_Service_MapsEngine_Icon");
}

/**
 * Return an icon or its associated metadata (icons.get)
 *
 * @param string $projectId The ID of the project.
 * @param string $id The ID of the icon.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_Icon
 */
public function get($projectId, $id, $optParams = array())
{
$params = array('projectId' => $projectId, 'id' => $id);
$params = array_merge($params, $optParams);
return $this->call('get', array($params), "Google_Service_MapsEngine_Icon");
}

/**
 * Return all icons in the current project (icons.listProjectsIcons)
 *
 * @param string $projectId The ID of the project.
 * @param array $optParams Optional parameters.
 *
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 50.
 * @return Google_Service_MapsEngine_IconsListResponse
 */
public function listProjectsIcons($projectId, $optParams = array())
{
$params = array('projectId' => $projectId);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_IconsListResponse");
}
}

/**
 * The "rasterCollections" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $rasterCollections = $mapsengineService->rasterCollections;
 *  </code>
 */
class Google_Service_MapsEngine_RasterCollections_Resource extends Google_Service_Resource
{

/**
 * Cancel processing on a raster collection asset.
 * (rasterCollections.cancelProcessing)
 *
 * @param string $id The ID of the raster collection.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_ProcessResponse
 */
public function cancelProcessing($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('cancelProcessing', array($params), "Google_Service_MapsEngine_ProcessResponse");
}

/**
 * Create a raster collection asset. (rasterCollections.create)
 *
 * @param Google_RasterCollection $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_RasterCollection
 */
public function create(Google_Service_MapsEngine_RasterCollection $postBody, $optParams = array())
{
$params = array('postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('create', array($params), "Google_Service_MapsEngine_RasterCollection");
}

/**
 * Delete a raster collection. (rasterCollections.delete)
 *
 * @param string $id The ID of the raster collection. Only the raster collection
 * creator or project owner are permitted to delete. If the rastor collection is
 * included in a layer, the request will fail. Remove the raster collection from
 * all layers prior to deleting.
 * @param array $optParams Optional parameters.
 */
public function delete($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('delete', array($params));
}

/**
 * Return metadata for a particular raster collection. (rasterCollections.get)
 *
 * @param string $id The ID of the raster collection.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_RasterCollection
 */
public function get($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('get', array($params), "Google_Service_MapsEngine_RasterCollection");
}

/**
 * Return all raster collections readable by the current user.
 * (rasterCollections.listRasterCollections)
 *
 * @param array $optParams Optional parameters.
 *
 * @opt_param string modifiedAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or after
 * this time.
 * @opt_param string createdAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or after
 * this time.
 * @opt_param string processingStatus
 * @opt_param string projectId The ID of a Maps Engine project, used to filter
 * the response. To list all available projects with their IDs, send a Projects:
 * list request. You can also find your project ID as the value of the
 * DashboardPlace:cid URL parameter when signed in to mapsengine.google.com.
 * @opt_param string tags A comma separated list of tags. Returned assets will
 * contain all the tags from the list.
 * @opt_param string search An unstructured search string used to filter the set
 * of results based on asset metadata.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 100.
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string creatorEmail An email address representing a user. Returned
 * assets that have been created by the user associated with the provided email
 * address.
 * @opt_param string bbox A bounding box, expressed as "west,south,east,north".
 * If set, only assets which intersect this bounding box will be returned.
 * @opt_param string modifiedBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or before
 * this time.
 * @opt_param string createdBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or before
 * this time.
 * @opt_param string role The role parameter indicates that the response should
 * only contain assets where the current user has the specified level of access.
 * @return Google_Service_MapsEngine_RasterCollectionsListResponse
 */
public function listRasterCollections($optParams = array())
{
$params = array();
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_RasterCollectionsListResponse");
}

/**
 * Mutate a raster collection asset. (rasterCollections.patch)
 *
 * @param string $id The ID of the raster collection.
 * @param Google_RasterCollection $postBody
 * @param array $optParams Optional parameters.
 */
public function patch($id, Google_Service_MapsEngine_RasterCollection $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('patch', array($params));
}

/**
 * Process a raster collection asset. (rasterCollections.process)
 *
 * @param string $id The ID of the raster collection.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_ProcessResponse
 */
public function process($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('process', array($params), "Google_Service_MapsEngine_ProcessResponse");
}
}

/**
 * The "parents" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $parents = $mapsengineService->parents;
 *  </code>
 */
class Google_Service_MapsEngine_RasterCollectionsParents_Resource extends Google_Service_Resource
{

/**
 * Return all parent ids of the specified raster collection.
 * (parents.listRasterCollectionsParents)
 *
 * @param string $id The ID of the raster collection whose parents will be
 * listed.
 * @param array $optParams Optional parameters.
 *
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 50.
 * @return Google_Service_MapsEngine_ParentsListResponse
 */
public function listRasterCollectionsParents($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_ParentsListResponse");
}
}
/**
 * The "permissions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $permissions = $mapsengineService->permissions;
 *  </code>
 */
class Google_Service_MapsEngine_RasterCollectionsPermissions_Resource extends Google_Service_Resource
{

/**
 * Remove permission entries from an already existing asset.
 * (permissions.batchDelete)
 *
 * @param string $id The ID of the asset from which permissions will be removed.
 * @param Google_PermissionsBatchDeleteRequest $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsBatchDeleteResponse
 */
public function batchDelete($id, Google_Service_MapsEngine_PermissionsBatchDeleteRequest $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('batchDelete', array($params), "Google_Service_MapsEngine_PermissionsBatchDeleteResponse");
}

/**
 * Add or update permission entries to an already existing asset.
 *
 * An asset can hold up to 20 different permission entries. Each batchInsert
 * request is atomic. (permissions.batchUpdate)
 *
 * @param string $id The ID of the asset to which permissions will be added.
 * @param Google_PermissionsBatchUpdateRequest $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsBatchUpdateResponse
 */
public function batchUpdate($id, Google_Service_MapsEngine_PermissionsBatchUpdateRequest $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('batchUpdate', array($params), "Google_Service_MapsEngine_PermissionsBatchUpdateResponse");
}

/**
 * Return all of the permissions for the specified asset.
 * (permissions.listRasterCollectionsPermissions)
 *
 * @param string $id The ID of the asset whose permissions will be listed.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsListResponse
 */
public function listRasterCollectionsPermissions($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_PermissionsListResponse");
}
}
/**
 * The "rasters" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $rasters = $mapsengineService->rasters;
 *  </code>
 */
class Google_Service_MapsEngine_RasterCollectionsRasters_Resource extends Google_Service_Resource
{

/**
 * Remove rasters from an existing raster collection.
 *
 * Up to 50 rasters can be included in a single batchDelete request. Each
 * batchDelete request is atomic. (rasters.batchDelete)
 *
 * @param string $id The ID of the raster collection to which these rasters
 * belong.
 * @param Google_RasterCollectionsRasterBatchDeleteRequest $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_RasterCollectionsRastersBatchDeleteResponse
 */
public function batchDelete($id, Google_Service_MapsEngine_RasterCollectionsRasterBatchDeleteRequest $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('batchDelete', array($params), "Google_Service_MapsEngine_RasterCollectionsRastersBatchDeleteResponse");
}

/**
 * Add rasters to an existing raster collection. Rasters must be successfully
 * processed in order to be added to a raster collection.
 *
 * Up to 50 rasters can be included in a single batchInsert request. Each
 * batchInsert request is atomic. (rasters.batchInsert)
 *
 * @param string $id The ID of the raster collection to which these rasters
 * belong.
 * @param Google_RasterCollectionsRastersBatchInsertRequest $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_RasterCollectionsRastersBatchInsertResponse
 */
public function batchInsert($id, Google_Service_MapsEngine_RasterCollectionsRastersBatchInsertRequest $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('batchInsert', array($params), "Google_Service_MapsEngine_RasterCollectionsRastersBatchInsertResponse");
}

/**
 * Return all rasters within a raster collection.
 * (rasters.listRasterCollectionsRasters)
 *
 * @param string $id The ID of the raster collection to which these rasters
 * belong.
 * @param array $optParams Optional parameters.
 *
 * @opt_param string modifiedAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or after
 * this time.
 * @opt_param string createdAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or after
 * this time.
 * @opt_param string tags A comma separated list of tags. Returned assets will
 * contain all the tags from the list.
 * @opt_param string search An unstructured search string used to filter the set
 * of results based on asset metadata.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 100.
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string creatorEmail An email address representing a user. Returned
 * assets that have been created by the user associated with the provided email
 * address.
 * @opt_param string bbox A bounding box, expressed as "west,south,east,north".
 * If set, only assets which intersect this bounding box will be returned.
 * @opt_param string modifiedBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or before
 * this time.
 * @opt_param string createdBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or before
 * this time.
 * @opt_param string role The role parameter indicates that the response should
 * only contain assets where the current user has the specified level of access.
 * @return Google_Service_MapsEngine_RasterCollectionsRastersListResponse
 */
public function listRasterCollectionsRasters($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_RasterCollectionsRastersListResponse");
}
}

/**
 * The "rasters" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $rasters = $mapsengineService->rasters;
 *  </code>
 */
class Google_Service_MapsEngine_Rasters_Resource extends Google_Service_Resource
{

/**
 * Delete a raster. (rasters.delete)
 *
 * @param string $id The ID of the raster. Only the raster creator or project
 * owner are permitted to delete. If the raster is included in a layer or
 * mosaic, the request will fail. Remove it from all parents prior to deleting.
 * @param array $optParams Optional parameters.
 */
public function delete($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('delete', array($params));
}

/**
 * Return metadata for a single raster. (rasters.get)
 *
 * @param string $id The ID of the raster.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_Raster
 */
public function get($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('get', array($params), "Google_Service_MapsEngine_Raster");
}

/**
 * Return all rasters readable by the current user. (rasters.listRasters)
 *
 * @param string $projectId The ID of a Maps Engine project, used to filter the
 * response. To list all available projects with their IDs, send a Projects:
 * list request. You can also find your project ID as the value of the
 * DashboardPlace:cid URL parameter when signed in to mapsengine.google.com.
 * @param array $optParams Optional parameters.
 *
 * @opt_param string modifiedAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or after
 * this time.
 * @opt_param string createdAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or after
 * this time.
 * @opt_param string processingStatus
 * @opt_param string tags A comma separated list of tags. Returned assets will
 * contain all the tags from the list.
 * @opt_param string search An unstructured search string used to filter the set
 * of results based on asset metadata.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 100.
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string creatorEmail An email address representing a user. Returned
 * assets that have been created by the user associated with the provided email
 * address.
 * @opt_param string bbox A bounding box, expressed as "west,south,east,north".
 * If set, only assets which intersect this bounding box will be returned.
 * @opt_param string modifiedBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or before
 * this time.
 * @opt_param string createdBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or before
 * this time.
 * @opt_param string role The role parameter indicates that the response should
 * only contain assets where the current user has the specified level of access.
 * @return Google_Service_MapsEngine_RastersListResponse
 */
public function listRasters($projectId, $optParams = array())
{
$params = array('projectId' => $projectId);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_RastersListResponse");
}

/**
 * Mutate a raster asset. (rasters.patch)
 *
 * @param string $id The ID of the raster.
 * @param Google_Raster $postBody
 * @param array $optParams Optional parameters.
 */
public function patch($id, Google_Service_MapsEngine_Raster $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('patch', array($params));
}

/**
 * Process a raster asset. (rasters.process)
 *
 * @param string $id The ID of the raster.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_ProcessResponse
 */
public function process($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('process', array($params), "Google_Service_MapsEngine_ProcessResponse");
}

/**
 * Create a skeleton raster asset for upload. (rasters.upload)
 *
 * @param Google_Raster $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_Raster
 */
public function upload(Google_Service_MapsEngine_Raster $postBody, $optParams = array())
{
$params = array('postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('upload', array($params), "Google_Service_MapsEngine_Raster");
}
}

/**
 * The "files" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $files = $mapsengineService->files;
 *  </code>
 */
class Google_Service_MapsEngine_RastersFiles_Resource extends Google_Service_Resource
{

/**
 * Upload a file to a raster asset. (files.insert)
 *
 * @param string $id The ID of the raster asset.
 * @param string $filename The file name of this uploaded file.
 * @param array $optParams Optional parameters.
 */
public function insert($id, $filename, $optParams = array())
{
$params = array('id' => $id, 'filename' => $filename);
$params = array_merge($params, $optParams);
return $this->call('insert', array($params));
}
}
/**
 * The "parents" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $parents = $mapsengineService->parents;
 *  </code>
 */
class Google_Service_MapsEngine_RastersParents_Resource extends Google_Service_Resource
{

/**
 * Return all parent ids of the specified rasters. (parents.listRastersParents)
 *
 * @param string $id The ID of the rasters whose parents will be listed.
 * @param array $optParams Optional parameters.
 *
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 50.
 * @return Google_Service_MapsEngine_ParentsListResponse
 */
public function listRastersParents($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_ParentsListResponse");
}
}
/**
 * The "permissions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $permissions = $mapsengineService->permissions;
 *  </code>
 */
class Google_Service_MapsEngine_RastersPermissions_Resource extends Google_Service_Resource
{

/**
 * Remove permission entries from an already existing asset.
 * (permissions.batchDelete)
 *
 * @param string $id The ID of the asset from which permissions will be removed.
 * @param Google_PermissionsBatchDeleteRequest $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsBatchDeleteResponse
 */
public function batchDelete($id, Google_Service_MapsEngine_PermissionsBatchDeleteRequest $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('batchDelete', array($params), "Google_Service_MapsEngine_PermissionsBatchDeleteResponse");
}

/**
 * Add or update permission entries to an already existing asset.
 *
 * An asset can hold up to 20 different permission entries. Each batchInsert
 * request is atomic. (permissions.batchUpdate)
 *
 * @param string $id The ID of the asset to which permissions will be added.
 * @param Google_PermissionsBatchUpdateRequest $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsBatchUpdateResponse
 */
public function batchUpdate($id, Google_Service_MapsEngine_PermissionsBatchUpdateRequest $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('batchUpdate', array($params), "Google_Service_MapsEngine_PermissionsBatchUpdateResponse");
}

/**
 * Return all of the permissions for the specified asset.
 * (permissions.listRastersPermissions)
 *
 * @param string $id The ID of the asset whose permissions will be listed.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_PermissionsListResponse
 */
public function listRastersPermissions($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_PermissionsListResponse");
}
}

/**
 * The "tables" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $tables = $mapsengineService->tables;
 *  </code>
 */
class Google_Service_MapsEngine_Tables_Resource extends Google_Service_Resource
{

/**
 * Create a table asset. (tables.create)
 *
 * @param Google_Table $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_Table
 */
public function create(Google_Service_MapsEngine_Table $postBody, $optParams = array())
{
$params = array('postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('create', array($params), "Google_Service_MapsEngine_Table");
}

/**
 * Delete a table. (tables.delete)
 *
 * @param string $id The ID of the table. Only the table creator or project
 * owner are permitted to delete. If the table is included in a layer, the
 * request will fail. Remove it from all layers prior to deleting.
 * @param array $optParams Optional parameters.
 */
public function delete($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('delete', array($params));
}

/**
 * Return metadata for a particular table, including the schema. (tables.get)
 *
 * @param string $id The ID of the table.
 * @param array $optParams Optional parameters.
 *
 * @opt_param string version
 * @return Google_Service_MapsEngine_Table
 */
public function get($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('get', array($params), "Google_Service_MapsEngine_Table");
}

/**
 * Return all tables readable by the current user. (tables.listTables)
 *
 * @param array $optParams Optional parameters.
 *
 * @opt_param string modifiedAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or after
 * this time.
 * @opt_param string createdAfter An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or after
 * this time.
 * @opt_param string processingStatus
 * @opt_param string projectId The ID of a Maps Engine project, used to filter
 * the response. To list all available projects with their IDs, send a Projects:
 * list request. You can also find your project ID as the value of the
 * DashboardPlace:cid URL parameter when signed in to mapsengine.google.com.
 * @opt_param string tags A comma separated list of tags. Returned assets will
 * contain all the tags from the list.
 * @opt_param string search An unstructured search string used to filter the set
 * of results based on asset metadata.
 * @opt_param string maxResults The maximum number of items to include in a
 * single response page. The maximum supported value is 100.
 * @opt_param string pageToken The continuation token, used to page through
 * large result sets. To get the next page of results, set this parameter to the
 * value of nextPageToken from the previous response.
 * @opt_param string creatorEmail An email address representing a user. Returned
 * assets that have been created by the user associated with the provided email
 * address.
 * @opt_param string bbox A bounding box, expressed as "west,south,east,north".
 * If set, only assets which intersect this bounding box will be returned.
 * @opt_param string modifiedBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been modified at or before
 * this time.
 * @opt_param string createdBefore An RFC 3339 formatted date-time value (e.g.
 * 1970-01-01T00:00:00Z). Returned assets will have been created at or before
 * this time.
 * @opt_param string role The role parameter indicates that the response should
 * only contain assets where the current user has the specified level of access.
 * @return Google_Service_MapsEngine_TablesListResponse
 */
public function listTables($optParams = array())
{
$params = array();
$params = array_merge($params, $optParams);
return $this->call('list', array($params), "Google_Service_MapsEngine_TablesListResponse");
}

/**
 * Mutate a table asset. (tables.patch)
 *
 * @param string $id The ID of the table.
 * @param Google_Table $postBody
 * @param array $optParams Optional parameters.
 */
public function patch($id, Google_Service_MapsEngine_Table $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('patch', array($params));
}

/**
 * Process a table asset. (tables.process)
 *
 * @param string $id The ID of the table.
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_ProcessResponse
 */
public function process($id, $optParams = array())
{
$params = array('id' => $id);
$params = array_merge($params, $optParams);
return $this->call('process', array($params), "Google_Service_MapsEngine_ProcessResponse");
}

/**
 * Create a placeholder table asset to which table files can be uploaded. Once
 * the placeholder has been created, files are uploaded to the
 * https://www.googleapis.com/upload/mapsengine/v1/tables/table_id/files
 * endpoint. See Table Upload in the Developer's Guide or Table.files: insert in
 * the reference documentation for more information. (tables.upload)
 *
 * @param Google_Table $postBody
 * @param array $optParams Optional parameters.
 * @return Google_Service_MapsEngine_Table
 */
public function upload(Google_Service_MapsEngine_Table $postBody, $optParams = array())
{
$params = array('postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('upload', array($params), "Google_Service_MapsEngine_Table");
}
}

/**
 * The "features" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mapsengineService = new Google_Service_MapsEngine(...);
 *   $features = $mapsengineService->features;
 *  </code>
 */
class Google_Service_MapsEngine_TablesFeatures_Resource extends Google_Service_Resource
{

/**
 * Delete all features matching the given IDs. (features.batchDelete)
 *
 * @param string $id The ID of the table that contains the features to be
 * deleted.
 * @param Google_FeaturesBatchDeleteRequest $postBody
 * @param array $optParams Optional parameters.
 */
public function batchDelete($id, Google_Service_MapsEngine_FeaturesBatchDeleteRequest $postBody, $optParams = array())
{
$params = array('id' => $id, 'postBody' => $postBody);
$params = array_merge($params, $optParams);
return $this->call('batchDelete', array($params));
}

/**
 * Append features to an existing table.
 *
 * A single batchInsert request can create:
 *
 * - Up to 50 features. - A combined total of 10
*/

