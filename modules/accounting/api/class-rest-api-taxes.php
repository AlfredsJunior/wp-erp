<?php
namespace WeDevs\ERP\Accounting\API;

use WP_REST_Server;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Tax_Rates_Controller extends \WeDevs\ERP\API\REST_Controller {
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'erp/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'accounting/v1/tax-rates';

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_tax_rates' ],
                'args'                => [],
                'permission_callback' => function ( $request ) {
                    return current_user_can( 'erp_ac_view_sale' );
                },
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_tax_rate' ],
                'args'                => [],
                'permission_callback' => function ( $request ) {
                    return current_user_can( 'erp_ac_create_sales_invoice' );
                },
            ],
            'schema' => [ $this, 'get_item_schema' ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_tax_rate' ],
                'args'                => [],
                'permission_callback' => function ( $request ) {
                    return current_user_can( 'erp_ac_view_sale' );
                },
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_tax_rate' ],
                'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                'permission_callback' => function ( $request ) {
                    return current_user_can( 'erp_ac_create_sales_invoice' );
                },
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'delete_tax_rate' ],
                'permission_callback' => function ( $request ) {
                    return current_user_can( 'erp_ac_create_sales_invoice' );
                },
            ],
            'schema' => [ $this, 'get_public_item_schema' ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/pay', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'pay_tax' ],
                'args'                => [],
                'permission_callback' => function ( $request ) {
                    return current_user_can( 'erp_ac_create_sales_payment' );
                },
            ],
            'schema' => [ $this, 'get_item_schema' ],
        ] );

    }

    /**
     * Get a collection of taxes
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_tax_rates( $request ) {
        $args = [
            'number' => !empty( $request['per_page'] ) ? $request['per_page'] : 20,
            'offset' => ( $request['per_page'] * ( $request['page'] - 1 ) ),
            'start_date' => empty( $request['start_date'] ) ? '' : $request['start_date'],
            'end_date' => empty( $request['end_date'] ) ? date('Y-m-d') : $request['end_date']
        ];

        $formatted_items = [];
        $additional_fields = [];

        $additional_fields['namespace'] = $this->namespace;
        $additional_fields['rest_base'] = $this->rest_base;

        $tax_data    = erp_acct_get_all_tax_rates( $args );
        $total_items = erp_acct_get_all_tax_rates( [ 'count' => true, 'number' => -1 ] );

        foreach ( $tax_data as $item ) {
            if ( isset( $request['include'] ) ) {
                $include_params = explode( ',', str_replace( ' ', '', $request['include'] ) );

                if ( in_array( 'created_by', $include_params ) ) {
                    $item['created_by'] = $this->get_user( $item['created_by'] );
                }
            }

            $data = $this->prepare_item_for_response( $item, $request, $additional_fields );
            $formatted_items[] = $this->prepare_response_for_collection( $data );
        }

        $response = rest_ensure_response( $formatted_items );
        $response = $this->format_collection_response( $response, $request, $total_items );

        $response->set_status( 200 );

        return $response;
    }


    /**
     * Get an tax
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_tax_rate( $request ) {
        $id = (int) $request['id'];

        if ( empty( $id ) ) {
            return new WP_Error( 'rest_tax_invalid_id', __( 'Invalid resource id.' ), [ 'status' => 404 ] );
        }

        $item = erp_acct_get_tax_rate( $id );

        $additional_fields['namespace'] = $this->namespace;
        $additional_fields['rest_base'] = $this->rest_base;

        $item  = $this->prepare_item_for_response( $item, $request, $additional_fields );
        $response = rest_ensure_response( $item );

        $response->set_status( 200 );

        return $response;
    }

    /**
     * Create an tax
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function create_tax_rate( $request ) {

        $tax_data = $this->prepare_item_for_database( $request );

        $items = $request['tax_components'];

        foreach ( $items as $key => $item ) {
            $item_rates[$key] = $item['tax_rate'];
        }

        $tax_data['total_rate'] = array_sum( $item_rates );

        $tax_id = erp_acct_insert_tax_rate( $tax_data );

        $tax_data['id'] = $tax_id;

        $additional_fields['namespace'] = $this->namespace;
        $additional_fields['rest_base'] = $this->rest_base;

        $tax_data = $this->prepare_item_for_response( $tax_data, $request, $additional_fields );

        $response = rest_ensure_response( $tax_data );
        $response->set_status( 201 );

        return $response;
    }

    /**
     * Update an tax
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function update_tax_rate( $request ) {
        $id = (int) $request['id'];

        if ( empty( $id ) ) {
            return new WP_Error( 'rest_tax_invalid_id', __( 'Invalid resource id.' ), [ 'status' => 404 ] );
        }

        $tax_data = $this->prepare_item_for_database( $request );

        $items = $request['tax_components'];

        foreach ( $items as $key => $item ) {
            $item_rates[$key] = $item['tax_rate'];
        }

        $tax_data['total_rate'] = array_sum( $item_rates );

        $tax_id = erp_acct_update_tax_rate( $tax_data, $id );

        $tax_data['id'] = $tax_id;
        $additional_fields['namespace'] = $this->namespace;
        $additional_fields['rest_base'] = $this->rest_base;

        $tax_data = $this->prepare_item_for_response( $tax_data, $request, $additional_fields );

        $response = rest_ensure_response( $tax_data );
        $response->set_status( 201 );

        return $response;
    }


    /**
     * Delete an tax
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Request
     */
    public function delete_tax_rate( $request ) {
        $id = (int) $request['id'];

        if ( empty( $id ) ) {
            return new WP_Error( 'rest_tax_invalid_id', __( 'Invalid resource id.' ), [ 'status' => 404 ] );
        }

        erp_acct_delete_tax_rate( $id );

        return new WP_REST_Response( true, 204 );
    }


    /**
     * Prepare a single item for create or update
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return array $prepared_item
     */
    protected function prepare_item_for_database( $request ) {
        $prepared_item = [];

        if ( isset( $request['tax_rate_name'] ) ) {
            $prepared_item['tax_rate_name'] = $request['tax_rate_name'];
        }
        if ( isset( $request['tax_number'] ) ) {
            $prepared_item['tax_number'] = $request['tax_number'];
        }
        if ( isset( $request['is_compound'] ) ) {
            $prepared_item['is_compound'] = $request['is_compound'];
        }
        if ( isset( $request['tax_components'] ) ) {
            $prepared_item['tax_components'] = $request['tax_components'];
        }

        return $prepared_item;
    }

    /**
     * Prepare a single user output for response
     *
     * @param array $item
     * @param WP_REST_Request $request Request object.
     * @param array $additional_fields (optional)
     *
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_item_for_response( $item, $request, $additional_fields = [] ) {
        $item = (object) $item;

        $data = [
            'id'              => (int) $item->id,
            'tax_name'        => $item->tax_rate_name,
            'tax_number'      => $item->tax_number,
            'tax_rate'        => $item->tax_rate,
            'default'         => $item->default,
            'tax_components'  => $item->tax_components,
        ];

        $data = array_merge( $data, $additional_fields );

        // Wrap the data in a response object
        $response = rest_ensure_response( $data );

        $response = $this->add_links( $response, $item, $additional_fields );

        return $response;
    }

    /**
     * Get the User's schema, conforming to JSON Schema
     *
     * @return array
     */
    public function get_item_schema() {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'tax',
            'type'       => 'object',
            'properties' => [
                'id'          => [
                    'description' => __( 'Unique identifier for the resource.' ),
                    'type'        => 'integer',
                    'context'     => [ 'embed', 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'tax_rate_name'  => [
                    'description' => __( 'Tax Rate name for the resource.' ),
                    'type'        => 'string',
                    'context'     => [ 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'tax_number'  => [
                    'description' => __( 'Tax number for the resource.' ),
                    'type'        => 'string',
                    'context'     => [ 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'is_compound'       => [
                    'description' => __( 'Tax type for the resource.' ),
                    'type'        => 'integer',
                    'context'     => [ 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'tax_components'       => [
                    'description' => __( 'Tax components for the resource.' ),
                    'type'        => 'object',
                    'context'     => [ 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'required'    => true,
                ],
            ],
        ];


        return $schema;
    }
}
