$(function(){

    const api_address_url = "//api-adresse.data.gouv.fr/search/";

    const select_state_element = document.getElementById('state_id');
    
    let options_state;

    if (select_state_element) {
        options_state = Array.from(select_state_element.children).map((option) => ( 
            {
                text: option.textContent,
                value: option.getAttribute('value'),
                zipcode:option.textContent.split('-')[0]
            }
        ));
    }

    let address = $('#address').val();
    const inputAddress = $('#address'); 

    const  error_msg_element = $('<p>Recherchez une addresse : </p>')
    let msgAddressNotFound = false;

    inputAddress.autocomplete({
        source: function(request, response){
            $.ajax({
                url: api_address_url,
                dataType: "json",
                data: {
                    q: request.term,
                    limit: 20
                },
                success: function(data){
                    var res = data.features;					
                    address = $('#address').val();

                    if(data.features.length === 0 && ! msgAddressNotFound)
                    {
                        $.jnotify(`L'adresse ${address} n'a pas été trouvée`, "error", 'true', { remove: function (){} } );
                        inputAddress.css('border', 'solid');	
                        inputAddress.css('borderColor', 'red');	
                        error_msg_element.insertBefore('#address');
                        // set msgAddressNotFound to true to avoid to display the notif 'L'adresse n'a pas été trouvée' several times
                        msgAddressNotFound = true;
                    }
                    else
                    {
                        msgAddressNotFound = false;
                        var list = [];
                        for(var i=0;i<res.length;i++){
                            list.push({
                                label:	res[i].properties.label,
                                name:		res[i].properties.name,
                                zip:		res[i].properties.postcode,
                                town:		res[i].properties.city,
                                citycode: res[i].properties.citycode
                            });
                        }

                        response(list);
                    }
                }
            });
        },

        select: function(event, ui){
            inputAddress.css('borderColor', '');	
            inputAddress.val(ui.item.name);
            error_msg_element.remove();

            $('#zipcode').val(ui.item.zip);
            $('#town').val(ui.item.town);

            // keep the first two digits
            let zipcode = parseInt(ui.item.zip.slice(0, 2));

            let citycode_option_value;
            let citycode_label;

            if (select_state_element) {
                // get the state label and state option
                // from the zipcode to update select #state_id
                for (option of options_state) {
                    if (zipcode == option.zipcode) {
                        citycode_label = option.text;
                        citycode_option_value = option.value;
                    }
                }

                // if zipcode not found in list
                // try with the first three digits (DOM TOM)
                if(! citycode_label) {
                    zipcode = parseInt(ui.item.zip.slice(0, 3));
                    for (option of options_state) {
                        if (zipcode == option.zipcode) {
                            citycode_label = option.text;
                            citycode_option_value = option.value;
                        }
                    }
                }

                // set the value and the label of the state select
                $('#state_id').val(citycode_option_value);
                $('#select2-state_id-container').text(citycode_label);
                $('#get_map_location').click();
            }
            return false;
        },
        minLength: 5
    });
});
