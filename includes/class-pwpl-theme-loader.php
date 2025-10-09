<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PWPL_Theme_Loader {
    private $sources = [];

    public function __construct() {
        $this->sources = $this->build_sources();
    }

    private function build_sources() {
        $sources = [];

        $uploads = wp_upload_dir( null, false );
        if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) && ! empty( $uploads['baseurl'] ) ) {
            $sources[] = [
                'id'  => 'uploads',
                'dir' => trailingslashit( $uploads['basedir'] ) . 'planify-themes',
                'url' => trailingslashit( $uploads['baseurl'] ) . 'planify-themes',
            ];
        }

        $stylesheet_dir = get_stylesheet_directory();
        $stylesheet_uri = get_stylesheet_directory_uri();
        if ( $stylesheet_dir && $stylesheet_uri ) {
            $sources[] = [
                'id'  => 'theme',
                'dir' => trailingslashit( $stylesheet_dir ) . 'planify-themes',
                'url' => trailingslashit( $stylesheet_uri ) . 'planify-themes',
            ];
        }

        $sources[] = [
            'id'  => 'plugin',
            'dir' => trailingslashit( PWPL_DIR ) . 'themes',
            'url' => trailingslashit( PWPL_URL ) . 'themes',
        ];

        return $sources;
    }

    public function get_theme( $slug ) {
        $slug = sanitize_key( $slug );
        if ( ! $slug ) {
            return null;
        }

        foreach ( $this->sources as $source ) {
            $dir = trailingslashit( $source['dir'] ) . $slug;
            if ( is_dir( $dir ) ) {
                return [
                    'slug'     => $slug,
                    'dir'      => $dir,
                    'url'      => trailingslashit( $source['url'] ) . $slug,
                    'manifest' => $this->load_manifest( $dir ),
                ];
            }
        }

        return null;
    }

    private function load_manifest( $dir ) {
        $manifest_path = trailingslashit( $dir ) . 'manifest.json';
        if ( ! file_exists( $manifest_path ) ) {
            return [];
        }

        $contents = file_get_contents( $manifest_path );
        if ( false === $contents ) {
            return [];
        }

        $data = json_decode( $contents, true );
        return is_array( $data ) ? $data : [];
    }

    public function get_assets( array $theme ) {
        $assets = [
            'css' => [],
            'js'  => [],
        ];

        if ( empty( $theme['manifest']['assets'] ) || ! is_array( $theme['manifest']['assets'] ) ) {
            return $assets;
        }

        $base_dir  = trailingslashit( $theme['dir'] );
        $base_url  = trailingslashit( $theme['url'] );
        $asset_map = $theme['manifest']['assets'];

        if ( ! empty( $asset_map['css'] ) && is_array( $asset_map['css'] ) ) {
            foreach ( $asset_map['css'] as $css ) {
                $css = ltrim( (string) $css, '/' );
                if ( ! $css ) {
                    continue;
                }
                $path = $base_dir . $css;
                if ( ! file_exists( $path ) ) {
                    continue;
                }
                $assets['css'][] = [
                    'handle' => sanitize_key( 'pwpl-theme-' . $theme['slug'] . '-css-' . md5( $css ) ),
                    'path'   => $path,
                    'url'    => $base_url . $css,
                ];
            }
        }

        if ( ! empty( $asset_map['js'] ) && is_array( $asset_map['js'] ) ) {
            foreach ( $asset_map['js'] as $js ) {
                $js = ltrim( (string) $js, '/' );
                if ( ! $js ) {
                    continue;
                }
                $path = $base_dir . $js;
                if ( ! file_exists( $path ) ) {
                    continue;
                }
                $assets['js'][] = [
                    'handle' => sanitize_key( 'pwpl-theme-' . $theme['slug'] . '-js-' . md5( $js ) ),
                    'path'   => $path,
                    'url'    => $base_url . $js,
                ];
            }
        }

        return $assets;
    }

    public function get_available_themes() {
        $themes = [];

        foreach ( $this->sources as $source ) {
            $base_dir = trailingslashit( $source['dir'] );
            $base_url = trailingslashit( $source['url'] );

            if ( ! is_dir( $base_dir ) ) {
                continue;
            }

            $entries = glob( $base_dir . '*', GLOB_ONLYDIR );
            if ( empty( $entries ) ) {
                continue;
            }

            foreach ( $entries as $dir ) {
                $manifest = $this->load_manifest( $dir );
                if ( empty( $manifest ) || empty( $manifest['slug'] ) ) {
                    continue;
                }

                $slug = sanitize_key( $manifest['slug'] );
                if ( ! $slug || isset( $themes[ $slug ] ) ) {
                    continue;
                }

                $themes[ $slug ] = [
                    'slug'     => $slug,
                    'name'     => isset( $manifest['name'] ) ? (string) $manifest['name'] : ucwords( str_replace( [ '-', '_' ], ' ', $slug ) ),
                    'manifest' => $manifest,
                    'dir'      => untrailingslashit( $dir ),
                    'url'      => $base_url . basename( $dir ),
                    'source'   => $source['id'],
                ];
            }
        }

        if ( ! isset( $themes['classic'] ) ) {
            $themes['classic'] = [
                'slug'     => 'classic',
                'name'     => __( 'Classic', 'planify-wp-pricing-lite' ),
                'manifest' => [],
                'dir'      => '',
                'url'      => '',
                'source'   => 'default',
            ];
        }

        return array_values( $themes );
    }
}
