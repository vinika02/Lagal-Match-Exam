<?php
/**
 * The default template for displaying content
 *
 * Used for both singular and index.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty
 * @since Twenty Twenty 1.0
 */
$find1 = is_active_sidebar( 'find-1' );
$find2 = is_active_sidebar( 'find-2' );
$find3 = is_active_sidebar( 'find-3' );
?>

<div id="popup-modal" class="modal">
    <div class="modal-content">
      <div class="modal-container">
        <span class="modal-close-btn">
          <span class="pop-up-close" id="reset-category" onclick="close_pop_up(this)"><?php twentytwenty_the_theme_svg( 'cross' ); ?></span>
       
        </span>

        <div class="modal-body">
          <div class="fam-select">
            <span class="num-3">3</span>
            Which <b>Family Law </b>issue(s) apply in your case?
          </div>
          <div class="row family-cat">
              <div class="column">
                  <div class="list"> <input type="checkbox" class="family-checkbox"> <label class="family-label"> Adoptions</label></div>
                  <div class="list"> <input type="checkbox" class="family-checkbox"> <label class="family-label">Guardianship</label></div>
                  <div class="list"> <input type="checkbox" class="family-checkbox"> <label class="family-label">Child Custoday and Visitation</label></div>
                  <div class="list"> <input type="checkbox" class="family-checkbox"> <label class="family-label">Paternity</label></div>
              </div>
              <div class="column">
                  <div class="list"> <input type="checkbox" class="family-checkbox"> <label class="family-label">Child Support</label></div>
                  <div class="list"> <input type="checkbox" class="family-checkbox"> <label class="family-label">Separation</label></div>
                  <div class="list"> <input type="checkbox" class="family-checkbox"> <label class="family-label">Divorce</label></div>
                  <div class="list"> <input type="checkbox" class="family-checkbox"> <label class="family-label">Spousal Support or Alimony</label></div>
              </div>
          </div>
          <br>
              <br>
              <br>
          <div class="">
            <center>
              <button type="button"  class="custom-btn-blue">FIND A LAWYER NOW</button>
            </center>
          </div>
       
        </div>
       
      </div>
    </div>
</div>
<div id="popup-category" class="modal">
    <div class="modal-content">
      <div class="modal-container">
        <span class="modal-close-btn">
          <span class="pop-up-close" id="reset-category" onclick="close_pop_up(this)"><?php twentytwenty_the_theme_svg( 'cross' ); ?></span>
       
        </span>

        <div class="modal-body">
          <div class="fam-select">           
            OTHER CATEGORIES           
          </div>
          <small>Choose the category that best fit in your case</small>
          <div class="row other-cat-content">
              <div class="column">
                  <div class="other-cat">Abuse (Child, Domestic, Sexual) </div>
                  <div class="other-cat">Agencies & Administration</div>
                  <div class="other-cat">Automobile ( DUI, Crimes, Speeding )</div>
                  <div class="other-cat">Automobiles ( Accidents, Insurance )</div>
                  <div class="other-cat">Banking ( Business, Consumer )</div>
                  <div class="other-cat">Bars & Restaurants</div>
                  <div class="other-cat">Business Formation & Dissolution</div>
                  <div class="other-cat">Children (Adoption, Custody, Support)</div>
                  <div class="other-cat">Class Actions (Bad Drugs, Products)</div>
                  <div class="other-cat">Commercial Law and Contracts</div>
                  <div class="other-cat">Commercial Real Estate</div>
                  <div class="other-cat">Constitutional Law Commercial Real Estate</div>
                  <div class="other-cat">Construction (Disputes, Liens)</div>
                  <div class="other-cat">Categories Item Here</div>
              </div>
              <div class="column">
                    <div class="other-cat">Loremipsum Dolorsitamet,</div>
                    <div class="other-cat"> Lonsectetur Scingelit  (Baseererkj, Mereit)</div>
                    <div class="other-cat"> Miam Euvelit</div>
                    <div class="other-cat"> Mempor Auctor (Estiarma, Toeruslej)</div>
                    <div class="other-cat"> Nisultricies Dictumest.</div>
                    <div class="other-cat"> Ntfring Sodalelit (Aeeticl Restoustoust)</div>
                    <div class="other-cat"> Ntvarius Lectus & Congue </div>
                    <div class="other-cat"> Pellentesque</div>
                    <div class="other-cat">  Rigula Aliquamattis (Nestusotu, Wewreist)</div>
                    <div class="other-cat">  Set Tigulempor</div>
                    <div class="other-cat">  Sehicula Sedsitamet</div>
                    <div class="other-cat">  Seque Nesturre </div>
                    <div class="other-cat"> Tollis Gaurissed</div>
                    <div class="other-cat"> Tempor Auctor (Estiarma, Toeruslej)</div>
                  
            </div>
          </div>
               
        </div>
       
      </div>
    </div>
  </div>
  <div id="popup-reviews" class="modal">
    <div class="modal-content rating-modal">
      <div class="modal-container">
          <div class="modal-header">           
             CLIENT REVIEWS       
          </div>
          <span class="modal-close-btn">
            
            <span class="pop-up-close" id="reset-category" onclick="close_pop_up(this)"><?php twentytwenty_the_theme_svg( 'cross' ); ?></span>
        
          </span>
          <div class="modal-body" id="reviews-modal-content">
                
          </div>   
          <div class="review-close">
              <span>CLOSE</span>  
          </div>    
      </div>
    </div>
  </div>

<div class="customize-container">
 <div class="c-content">
  <div class="customize-content">
    <?php if ( $find1 ) { ?>
      <?php dynamic_sidebar( 'find-1' ); ?>
    <?php } ?>
    

  </div>
  <div class="row">
    <div class="column col-left">
      <?php if ( $find2 ) { ?>
        <?php dynamic_sidebar( 'find-2'); ?>
      <?php } ?>     

        
        <!-- <select class="category" id="category" onchange="category_modal()">
          <option hidden>Click to choose a legal category</option>
         
        </select> -->
        <label class="zp">Enter Zip Code or City</label>
        <p class="wrapper">
        <input type="text" placeholder="Does not have to be where you live" class="zip-or-city">
        </p>

        <div class="container-select">    
          <label class="ccat">Choose Category</label>
          <p class="wrapper2">	
          <input type="text" class="select-label category" onclick="show_category(this)" id="category" readonly placeholder="Click to choose a legal category" >
          </p>
          <div class="select-wrap" id="select-wrap">
            <ul class="select">
              <li onclick="category_modal(this)">Family</li>
              <li>Criminal Defense</li>
              <li>Business</li>
              <li>Personal Injury</li>
              <li>Bankruptcy & Finances</li>
              <li>Product & Services</li>
              <li>Employment</li>
              <li>Real State</li>
              <li>Immigration</li>
              <li>Wills, Trust & State</li>
              <li>Government</li>
              <li>Intellectual Property</li>
            </ul>
          </div>
      
   
      </div>
      <div class="cant-find-cat">
          Can't find your category? <u id="other-cat">Click here</u>
       </div>
    
    </div>
    <div class="column col-content-2">
      <?php if ( $find2 ) { ?>
          <?php dynamic_sidebar( 'find-3'); ?>
       <?php } ?>         
        <div id="lawyer-reviews"></div>
        
    </div>
  </div>
 </div>
</div>



