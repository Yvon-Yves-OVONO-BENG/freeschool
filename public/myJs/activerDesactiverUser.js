document.addEventListener('DOMContentLoaded', function() {
    $('.custom-switch-input').change(function() {
        let utilisateurId = $(this).data('id');
        let isChecked = $(this).is(':checked');
        console.log(isChecked );
        ///je cree un nouvel objet XMLHttpRequest
			var xhr = new XMLHttpRequest();
			xhr.open('POST', '/freeschool/public/administrators/eneable-diseable-administrator', true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onreadystatechange = function() 
			{
				if (xhr.readyState === 4 && xhr.status === 200) 
				{
					var response = JSON.parse(xhr.responseText);
					if (response.success) 
					{
						if (isChecked == false) {
							notif({
								msg: "<b>Administrator eneabled with success !</b>",
								type: "success",
								position: "right",
								width: 500,
								height: 60,
								autohide: true
								});
						}
						else if(isChecked == true){
							notif({
								msg: "<b>Administrator disabled with success !</b>",
								type: "error",
								position: "left",
								width: 500,
								height: 60,
								autohide: true
								});
						}
					}
					else 
					{
						notif({
							msg: "<b>Erreur lors de la mise à jour du utilisateur !</b>",
							type: "danger",
							position: "left",
							width: 500,
							height: 60,
							autohide: true
							});
					}
				}
			};
			
			//j'envoie la request avec le utilisateur ID
			xhr.send('utilisateur_id=' + utilisateurId);
        
    });
});