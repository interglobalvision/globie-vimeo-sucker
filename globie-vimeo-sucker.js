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
    xmlhttp.setRequestHeader("Authorization", "bearer a0c52130c00d1382bb992ebc59abc9cf");
    xmlhttp.setRequestHeader("Authorization", "bearer a0c52130c00d1382bb992ebc59abc9c");
    xmlhttp.onreadystatechange = function() {
      console.log(xmlhttp.responseText);
      if( xmlhttp.readyState == 4 ) {
        if( xmlhttp.status == 200 ) {
          var vimeoData = JSON.parse(xmlhttp.responseText);

          console.log(vimeoData.width);
          console.log(vimeoData.height);

          // Set title
          document.getElementById('title').focus();
          document.getElementById('title').value = vimeoData.name;

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
