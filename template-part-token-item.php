<?php 

//++++++++++++++++++++++
//++
//+++ Fontend token item 
//++
//++++++++++++++++++++++

function custom_post_row() { 
    ob_start();

	acf_form_head();
	date_default_timezone_set('Europe/Warsaw');	

    $post_id = get_the_ID();

	$extra_class = '';

	$terms_category_tokens = get_the_terms( $post_id ,  'category_tokens' );
	if ($terms_category_tokens){
		foreach ( $terms_category_tokens as $term2 ) {					
			if ($term2->name == "Scam"){
				$extra_class = ' warning';
			}
			if ($term2->name == "Gem"){
				$extra_class = ' gem1';
			}			
		}
	}
	$update_date = '-';

	// PART FOR WP ALL IMPORT
	if (get_post_meta($post_id,'history_last_update',true)){
		$update_date = get_post_meta($post_id,'history_last_update',true);
		$update_date = DateTime::createFromFormat('d-m-y h:i', $update_date);
		$update_date = $update_date->format('d-m-y');
	}
	$terms_token_status = get_the_terms( $post_id ,  'blockchain' );
	foreach ( $terms_token_status as $term ) {	 
		$blockchain = $term->name;
	}
	?>
	<div class="token_list<?php echo $extra_class; ?>">
		<div class="left_part">
			<div class="token_item_row">


				<h3 class="uabb-post-heading uabb-blog-post-section"><a href="<?php echo get_permalink(); ?>" target="_blank"><?php echo get_the_title(); ?></a></h3>
				<small><span>Address:</span> <strong><?php echo get_field('address'); ?></strong></small>
				
				<div class="right_top_row">
				<?php
				
				if (get_post_meta( $post_id, 'purge_reason', true )){
					echo '<span class="badge_item scam">🛑 '. get_post_meta( $post_id, 'purge_reason', true ) .' 🛑</span>';
				}
			

				if ($blockchain == 'Ethereum'){
				?>
				<div class="links">
					<a target="_blank" href="https://etherscan.io/token/<?php echo get_field('address'); ?>">Etherscan</a> 
					<a target="_blank" href="https://etherscan.io/token/<?php echo get_field('address'); ?>#code">Contract</a> 

					<a class="yelow" target="_blank" href="https://honeypot.is/ethereum?address=<?php echo get_field('address'); ?>">HoneyPot</a> 
					<a class="yelow" target="_blank" href="https://gopluslabs.io/token-security/1/<?php echo get_field('address'); ?>">GoLabs</a> 
					<a class="yelow" target="_blank" href="https://api.gopluslabs.io/api/v1/token_security/1?contract_addresses=<?php echo $token_address; ?>">GoLabs API</a>
					<a class="yelow" target="_blank" href="https://tokensniffer.com/token/eth/<?php echo get_field('address'); ?>">Sniffer</a> 
					<a class="yelow" target="_blank" href="https://moonscan.com/scan/contract/eth/<?php echo get_field('address'); ?>">Moonscan</a> 
				</div>					
				<div class="links">
					<a class="blue" target="_blank" href="https://www.cryptogems.info/eth/token/<?php echo get_field('address'); ?>">CryptoGems</a> 
					<a class="blue" target="_blank" href="https://www.dextools.io/app/en/ether/pair-explorer/<?php echo get_field('address'); ?>">Dex Tools</a> 
					<a class="blue" target="_blank" href="https://dexscreener.com/ethereum/<?php echo get_field('address'); ?>">Dex Screener</a> 
					<a class="blue" target="_blank" href="https://isrug.app/ethereum/<?php echo get_field('address'); ?>">IsRug</a> 

					<a target="_blank" href="https://www.followeraudit.com/">Follower Audit</a> 

				</div>					
				<?php
				}
				if ($blockchain == 'Arbitrum'){
				?>
				<div class="links">
					<a target="_blank" href="https://arbiscan.io/token/<?php echo get_field('address'); ?>">Arbiscan</a> 
					<a target="_blank" href="https://arbiscan.io/token/<?php echo get_field('address'); ?>#code">Contract</a> 

					<a class="yelow" target="_blank" href="https://gopluslabs.io/token-security/42161/<?php echo get_field('address'); ?>">GoLabs</a> 
					<a class="yelow" target="_blank" href="https://api.gopluslabs.io/api/v1/token_security/42161?contract_addresses=<?php echo $token_address; ?>">GoLabs API</a> 
					<a class="yelow" target="_blank" href="https://tokensniffer.com/token/eth/<?php echo get_field('address'); ?>">Sniffer</a> 
					<a class="yelow" target="_blank" href="https://moonscan.com/scan/contract/arbi/<?php echo get_field('address'); ?>">Moonscan</a> 
				</div>					
				<div class="links">
					<a class="blue" target="_blank" href="https://www.dextools.io/app/en/arbitrum/pair-explorer/<?php echo get_field('address'); ?>">Dex Tools</a> 
					<a class="blue" target="_blank" href="https://dexscreener.com/arbitrum/<?php echo get_field('address'); ?>">Dex Screener</a> 
					<a class="blue" target="_blank" href="https://isrug.app/arbitrum/<?php echo get_field('address'); ?>">IsRug</a> 

					<a target="_blank" href="https://www.followeraudit.com/">Follower Audit</a> 

				</div>					
				<?php
				}?>
				</div>
			</div> 	
			<div class="token_item_row">
				<?php if (get_field('creatorcontract')){ ?>
					<div>
						<?php
						if ($blockchain == 'Ethereum'){ ?>
						<div><small>Deployer address:</small> <a href="https://etherscan.io/address/<?php echo get_field('creatorcontract'); ?>" target="_new"><?php echo get_field('creatorcontract'); ?></a></div>
						<?php
						}
						if ($blockchain == 'Arbitrum'){ ?>
						<div><small>Deployer address:</small> <a href="https://arbiscan.io/address/<?php echo get_field('creatorcontract'); ?>" target="_new"><?php echo get_field('creatorcontract'); ?></a></div>
						<?php
						}
						?>
						<div><small>Deployer balance:</small> <?php echo get_field('deploy_balance'); ?> ETH</div>
					</div>
				<?php } ?>		
			</div>
			<div class="token_item_row">
			<?php 
				echo '<div class="taxonomy badges">';

				

				$tx_0xf305d719 = get_post_meta( $post_id, 'tx_0xf305d719', true );
				if ($tx_0xf305d719){							
					echo '<span class="badge_item add_lq">' . $tx_0xf305d719 . '</span>';			
				}
				$tx_0x715018a6 = get_post_meta( $post_id, 'tx_0x715018a6', true );
				if ($tx_0x715018a6){							
					echo '<span class="badge_item">' . $tx_0x715018a6 . '</span>';			
				}
				$tx_0x5dae9672 = get_post_meta( $post_id, 'tx_0x5dae9672', true );
				if ($tx_0x5dae9672){							
					echo '<span class="badge_item">' . $tx_0x5dae9672 . '</span>';			
				}
				$tx_0x293230b8 = get_post_meta( $post_id, 'tx_0x293230b8', true );
				if ($tx_0x293230b8){							
					echo '<span class="badge_item">' . $tx_0x293230b8 . '</span>';			
				}
				$tx_0x8af416f6 = get_post_meta( $post_id, 'tx_0x8af416f6', true );
				if ($tx_0x8af416f6){							
					echo '<span class="badge_item lock_lp">' . $tx_0x8af416f6 . '</span>';						
				}

				// Scam // zostawiamy
				$tx_scam1 = get_post_meta( $post_id, 'tx_scam1', true );
				if (is_array($tx_scam1)){							
					echo '<span class="badge_item scam">🛑 <b>SCAM</b> 🛑</span>';			
				}
				echo '</div>';

				?>

			</div>
			<div class="token_item_row">
				<?php 
				$targetFunctionNames = [
					'renounceOwnership',
					'addLiquidityETH',
					'lockLPToken',
					'removeLimits',
					'openTrading',
					'updateBuyFees',
					'updateSellFees',
					'setFee',
					'setMaxWalletSize',
					'lock',
					'removeLiquidityWithPermit',
					
				];

				echo '<ul class="changelog">';

				$create_contract = get_field('create_date');
				if ($create_contract){				
					if (is_object($create_contract)) {	
						$create_contract->modify('+1 hour');
						echo '<li class="create_contract"><span class="tooltip"><small>Contract Deployed: ' . $create_contract->format("d-m-y"). '</small> ' . $create_contract->format("H:i:s") . ' <span>Czas wykrycia kontraktu przez Tomka.</span></span></li>';		
					} else {
						echo '<li class="create_contract"><span class="tooltip"><small>Contract Deployed: </small> ' . $create_contract . '<span>Czas wykrycia kontraktu przez Tomka. Token dodany do bazy wp '.  get_the_date().' '. get_the_time().'</span></span></li>';			
					}
				}

				$contract_verification_date = get_post_meta( $post_id, 'contract_verification_date', true );
				if ($contract_verification_date){	
					$date = DateTime::createFromFormat('d-m-y H:i:s', $contract_verification_date);
					$contract_verification_date = $date->modify('+1 hour');			
					echo '<li class="contract_verification_date"><span class="tooltip"><small>Contract Verified: ' . $contract_verification_date->format("d-m-y"). '</small> ' . $contract_verification_date->format("H:i:s") . ' <span>Data wykrycia: ' . $contract_verification_date->format("d-m-y H:i:s") . '</span></span></li>';		
				}


				foreach ($targetFunctionNames as $fieldName) {
					$fieldValue = get_post_meta($post_id, $fieldName, true);
					if($fieldValue){
						$date = DateTime::createFromFormat('d-m-y H:i:s', $fieldValue['etherscan_date']);
						if($date){
							$date_time = $date->modify('+1 hour');	
							$date = $date_time->format('d-m-y');	
							$time = $date_time->format('H:i:s');	
							echo '<li class="' . $fieldName . '"><span class="tooltip"><small>' . $fieldName . ': ' . $date . '</small> ' . $time . ' <span>Data wykrycia: ' . $fieldValue['current_date'] . '</span></span></li>';
						}
					}
				}

				echo '</ul>';
				
					$posts = get_posts(array(
						'posts_per_page'    => -1,
						'exclude' => $post_id,
						'post_type'         => 'tokens',
						'meta_query' => array(
							'relation' => 'AND',
							array(
								'key' => 'name',
								'value' => get_field('name'),
								'compare' => '=',
							),
							array(
								'key' => 'symbol',
								'value' => get_field('symbol'),
								'compare' => '=',
							),
						),					
					));

					if( $posts ): ?>
					<div class="other_deploys"> 
						<h3>Duplicates</h3>						
						<ul>
						<?php foreach( $posts as $post ): ?>
							<li>
								<a href="<?php the_permalink($post->ID); ?>" target="_new"><?php echo $post->post_title; ?> / <?php echo $post->post_date; ?></a>								
							</li>						
						<?php endforeach; ?>						
						</ul>
					</div>

				<?php endif; 
				
				if (get_field('creatorcontract')){ 
					$posts = get_posts(array(
						'posts_per_page'    => -1,
						'exclude' => $post_id,
						'post_type'         => 'tokens',
						'meta_key'      => 'creatorcontract',
						'meta_value'    => get_field('creatorcontract')					
					));

					if( $posts ): ?>
					<div class="other_deploys">
						<h3>Other deploys</h3>						
						<ul>
						<?php foreach( $posts as $post ): ?>
							<li>
								<a href="<?php the_permalink($post->ID); ?>" target="_new"><?php echo $post->post_title; ?> / <?php echo $post->post_date; ?></a>								
							</li>						
						<?php endforeach; ?>						
						</ul>
					</div>
					<?php endif; ?>
				<?php } 
				if (get_post_meta( $post_id, 'get_contract_owner_tansactions_counter',true)) { ?>
					<small style="margin:15px 0;display: block;"><span>Contract Owner Checked: <?php  echo get_post_meta( $post_id, 'get_contract_owner_tansactions_counter',true); ?> times</span></small> 
				<?php  }  ?>
				<small>
					<?php the_content(); ?>					
				</small>

				<div class="links">
					<?php if (get_field('twitter_account_url')){
						echo '<a target="_blank" href="' . get_field('twitter_account_url') .'">Twitter</a>';
					} ?>
					<?php if (get_field('twitter_account_name')){
						echo '<a target="_blank" href="https://socialbearing.com/search/user/' . get_field('twitter_account_name') .'">Social Bearing USER</a>';
						echo '<a target="_blank" href="https://socialblade.com/twitter/user/' . get_field('twitter_account_name') .'">Social Blade</a>';

					} 
					?>
					<a target="_blank" href="https://twitter.com/search?q=%24<?php echo str_replace(array('�', chr(24)), '', get_field('symbol')); ?>&src=typed_query&f=live">Twitter  $<?php echo str_replace(array('�', chr(24)), '', get_field('symbol')); ?></a> 
					<a target="_blank" href="https://socialbearing.com/search/general/$<?php echo str_replace(array('�', chr(24)), '', get_field('symbol')); ?>">Social Bearing  $<?php echo str_replace(array('�', chr(24)), '', get_field('symbol')); ?></a> 
					<?php if (get_field('telegram_url')){
						echo '<a target="_blank" href="' . get_field('telegram_url') .'">Telegram</a>';
					} ?>
					<?php if (get_field('medium_url')){
						echo '<a target="_blank" href="' . get_field('medium_url') .'">Medium</a>';
					} ?>
					<?php if (get_field('website')){
						echo '<a target="_blank" href="' . get_field('website') .'">Website</a>';
					} ?>
				</div>
			</div>	
			<div class="token_item_row">
				<div class="item234">
				<?php if (get_field('telegram_subscribers')){
					echo '<div>Telegram subscribers: </div>' . get_field('telegram_subscribers'); 
				} ?>
				</div>
				<div class="item234">
				<?php if (is_array(get_field('twitter_followers_updated'))) {
					$array = get_field('twitter_followers_updated');
					echo '<div>Twitter followers: </div><span class="tooltip">' . $array['count'] . '<span>Sprawdzono '.   $array['updated_at'] .' - Ilość followersów po wykryciu projektu ' . get_field('twitter_followers') .'</span></span>';
				} ?>
				</div>
				<div class="item234">
				<?php if (get_field('medium_followers')){
					echo '<div>Medium followers: </div>' . get_field('medium_followers'); 
				} ?>
				</div>
			</div> 
			<div class="token_item_row dexscreener_data">
				<?php 
				if (get_post_meta( $post_id, 'priceUsd', true )){
					echo '<span class="tooltip"><small>Price: $' . get_post_meta( $post_id, 'priceUsd', true ) . '</small><span>Sprawdzono '.  date('H:i:s d-m-Y', get_post_meta( $post_id, 'last_fetch_time', true )) .'</span></span>';
				}
				if (get_post_meta( $post_id, 'liquidity', true )){
					echo '<span class="tooltip"><small>Liquidity: $' . format_number(get_post_meta( $post_id, 'liquidity', true )) . '</small><span>Sprawdzono '.  date('H:i:s d-m-Y', get_post_meta( $post_id, 'last_fetch_time', true )) .'</span></span>';
				}
				if (get_post_meta( $post_id, 'marketcap', true )){
					echo '<span class="tooltip"><small>Market Cap: $' . format_number(get_post_meta( $post_id, 'marketcap', true )) . '</small><span>Sprawdzono '.  date('H:i:s d-m-Y', get_post_meta( $post_id, 'last_fetch_time', true )) .'</span></span>';
				}
				if (get_post_meta( $post_id, 'priceChange', true )){
					echo '<span class="tooltip"><small>Change 24h: ' . get_post_meta( $post_id, 'priceChange', true ) . '%</small><span>Sprawdzono '.  date('H:i:s d-m-Y', get_post_meta( $post_id, 'last_fetch_time', true )) .'</span></span>';
				} 
				?>
			</div>
			<div class="token_item_row dexscreener_data">
				<?php 
				if (get_post_meta( $post_id, 'token_high_price', true )){
					echo '<small>Highest price: $' . get_post_meta( $post_id, 'token_high_price', true ) . '</small><br/>';
				}
				if (get_post_meta( $post_id, 'token_low_price', true )){
					echo '<small>Lowest price: $' . get_post_meta( $post_id, 'token_low_price', true ) . '</small><br/>';
				}
				if (get_post_meta( $post_id, 'fibonacci_position', true )){
					echo '<small>Aktualna cena znajduje się w strefie ' . get_post_meta( $post_id, 'fibonacci_position', true ) . '</small>';
				}

				

				if (get_post_meta( $post_id, 'buy_volume', true )){
					echo '<span class="badge_item volume">Buy Volume since contract created '. round(get_post_meta( $post_id, 'buy_volume', true ),2) .' ETH</span>';
				}
				?>
			</div>
		</div>
		<div class="right_part">
			
			<div class="token_item_row top_right">
			<?php
				echo '<div class="taxonomy badges">';
				$terms_token_status = get_the_terms( $post_id ,  'blockchain' );
				if ($terms_token_status){
					foreach ( $terms_token_status as $term3 ) {					
						echo '<span class="tax_token_status">' . $term3->name . '</span>';
					}
				}
				$terms_token_status = get_the_terms( $post_id ,  'token_status' );
				if ($terms_token_status){
					foreach ( $terms_token_status as $term3 ) {					
						echo '<span class="tax_token_status">' . $term3->name . '</span>';
					}
				}
				$terms_category_tokens = get_the_terms( $post_id ,  'test' );
				if ($terms_category_tokens){
					foreach ( $terms_category_tokens as $term2 ) {					
						echo '<span class="tax_category_tokens">' . $term2->name . '</span>';
					}
				}
				$terms_group = get_the_terms( $post_id , 'group' );
				if ($terms_group){
					foreach ( $terms_group as $term1 ) {				
						echo '<span class="tax_group">' . $term1->name . '</span>';
					}
				}		
				echo '</div>';			
				
				?>
				<span><?php echo do_shortcode( '[frontend_admin form=20913 edit=false]' ); ?></span>
				<button class="show_details" data-id="<?php echo $post_id; ?>">Chart</button>
				
			</div>		
			<div class="details_part hidden">
				<div class="ajax_part" data-id="<?php echo $post_id; ?>"></div>
				<?php // comment_form('', $post_id ); 
				
				// acf_form(array(
				// 	'post_id'       => $post_id,
				// 	'post_title'    => false,
				// 	'post_content'  => true,
				// 	'submit_value'  => __('Aktualizuj')
				// ));
				
				?>
			</div>
			
		</div>
	</div>
	<?php

    return ob_get_clean();
} 
add_shortcode('custom_post_row', 'custom_post_row'); 
//+++ END +++ Fontend token item 