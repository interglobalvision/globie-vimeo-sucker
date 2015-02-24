document.addEventListener("DOMContentLoaded", function() {
  var suckDataButton = document.getElementById('suck-vimeo-data');
  suckDataButton.addEventListener("click", function(e) {
    e.preventDefault();

    vimeoId = document.getElementById('gvsucker-id-field');
    if( vimeoId.value == '' ) {
      alert('Vimeo ID needed');
      return;
    }

    // Turn on spinner
    document.getElementById('globie-spinner').style.display = "inline-block";

    // Get access token
    // TODO: Get token from hidden field

    // Get video data
    var xmlhttp;
    if (window.XMLHttpRequest) {
      xmlhttp = new XMLHttpRequest();
    } else {
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.open('GET', 'https://api.vimeo.com/videos/' + vimeoId.value, true);
    xmlhttp.setRequestHeader("Authorization", "bearer a0c52130c00d1382bb992ebc59abc9cf");
    xmlhttp.onreadystatechange = function() {
      console.log(xmlhttp.responseText);
      if( xmlhttp.readyState == 4 ) {
        if( xmlhttp.status == 200 ) {
          var vimeoData = JSON.parse(xmlhttp.responseText);

          console.log(vimeoData.width);
          console.log(vimeoData.height);

          // Split the vimeo title
          var titleArray = vimeoData.name.split('"');
          //         console.log(titleArray);

          // Tidy the split strings. input must follow exact spacing pattern for this to work
          var videoBrand = titleArray[0].substring(0, titleArray[0].length-1);
          var videoTitle = titleArray[1];
          var videoDirector = titleArray[2].substring(4, titleArray[2].length);

          // Set title
          document.getElementById('title').focus();
          document.getElementById('title').value = vimeoData.name;

          // Set meta
          document.getElementById('_igv_title').value = videoTitle;
          document.getElementById('_igv_brand').value = videoBrand;
          selectOption('_igv_director', videoDirector);

          // Set content
          window.switchEditors.switchto({id: "content-html"});
          document.getElementById('content').value = vimeoData.description;
          window.switchEditors.switchto({id: "content-tmce"});

          // Set tags
          var tagsList = '', 
          whitelist = gVSucker.whitelist.toLowerCase();
          vimeoData.tags.forEach( function(tag, index, tags) {
            // Check if tag is in the whitelist
            // If the whitelist is empty all pass
            if( !whitelist || whitelist.indexOf(tag.tag.toLowerCase() ) != -1)
              tagsList += tag.name + ", ";
          });
          document.getElementById('new-tag-post_tag').value = tagsList;

          // Set video values
          document.getElementById('gvsucker-width-field').value = vimeoData.width;
          document.getElementById('gvsucker-height-field').value = vimeoData.height;
          document.getElementById('gvsucker-ratio-field').value = (vimeoData.height/vimeoData.width);

          // Set featured image
          var inside = document.getElementById('postimagediv').getElementsByClassName('inside')[0];
          inside.innerHTML = '';

          //    Set Thumbnail
          var featImg = document.createElement('img');
          featImg.setAttribute('id', 'gvsucker-img');
          featImg.setAttribute('src', vimeoData.pictures.sizes[2].link);
          featImg.setAttribute('width', 266);
          inside.appendChild(featImg);

          //    Set url in hidden field
          document.getElementById('gvsucker-img-field').value = vimeoData.pictures.sizes[4].link;
        }
      } else {
        var responseText = JSON.parse(xmlhttp.responseText);
        if( responseText.error)
          alert("Vimeo Error: " + responseText.error);
      }

      // Turn off spinner
      document.getElementById('globie-spinner').style.display = "none";
    }
    xmlhttp.send();
  });
});

//  This function selects an option in a select according to its text value
function selectOption(selectId, selectText) {
  var select = document.getElementById(selectId);
  for (var i = 0; i < select.length; i++) {
    if (select.options[i].text == selectText ) {
      select.selectedIndex = i;
    }
  }
}
