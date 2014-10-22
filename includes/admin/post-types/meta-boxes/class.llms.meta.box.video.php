<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
* Meta Box Video
*
* diplays text input for oembed video
*
* @version 1.0
* @author codeBOX
* @project lifterLMS
*/
class LLMS_Meta_Box_Video {

	/**
	 * Set up video input
	 *
	 * @return string
	 * @param string $post
	 */
	public static function output( $post ) {
		global $post;
		wp_nonce_field( 'lifterlms_save_data', 'lifterlms_meta_nonce' );

		$video_embed = get_post_meta( $post->ID, '_video_embed', true );
		?>

		<table class="form-table">
		<tbody>
			<tr>
				<th>
					<?php
					$label  = '';
					$label .= '<label for="_video_embed">' . __( 'Video Embed Code', 'lifterlms' ) . '</label> ';
					echo $label;
					?>
				</th>
				<td>
					<?php
					$html  = '';
					$html .= '<input type="text" class="code" name="_video_embed" id="_video-embed" value="' . $video_embed . '"/>';
					$html .= '<br /><span class="description">' .  __( 'Paste the url for your Wistia, Vimeo or Youtube videos.', 'lifterlms' ) . '</span>';
					echo $html;
					?>
				</td>
			</tr>

			<tr>
				<th>
					<?php
					$label  = '';
					$label .= '<label for="_audio_embed">' . __( 'Audio Embed Code', 'lifterlms' ) . '</label> ';
					echo $label;
					?>
				</th>
				<td>
					<?php
					$html  = '';
					$html .= '<input type="text" class="code" name="_audio_embed" id="_audio-embed" value="' . $audio_embed . '"/>';
					$html .= '<br /><span class="description">' .  __( 'Paste the embed code for your externally hosted audio.', 'lifterlms' ) . '</span>';
					echo $html;
					?>
				</td>
			</tr>
		</tbody>
		</table>

		<?php  
	}

	public static function save( $post_id, $post ) {
		global $wpdb;

		if ( isset( $_POST['_video_embed'] ) ) {

			$video = ( llms_clean( $_POST['_video_embed']  ) );

			update_post_meta( $post_id, '_video_embed', ( $video === '' ) ? '' : $video );
			
		}
	}

}