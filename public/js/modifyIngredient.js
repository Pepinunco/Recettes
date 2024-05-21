let ingredientIndex = 0;
const container = document.getElementById('ingredient-container');
const prototype = document.querySelector('.ingredient-form').outerHTML;

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[name="recette_ingredient[Ingredient]"]').forEach(selectElement =>{
        selectElement.addEventListener('change', event=> {
            const ingredientId = event.target.value;
            fetch(`getIngredientUnit/${ingredientId}`)
                .then(response=>response.json() )
                .then(data=>{
                    const quantiteInput = event.target.closest('.ingredient-form').querySelector('input[name="recette_ingredient[Quantite]"]');
                    quantiteInput.placeholder = data.unit;
                });
        });
    });
})

document.getElementById('ingredient-form').addEventListener("submit", (event)=> {

    const forms = document.querySelectorAll('.ingredient-form');
    const data = [];

    forms.forEach(form=>{
        const ingredient = form.querySelector('select[name="recette_ingredient[Ingredient]"]').value;
        const quantity = form.querySelector('input[name="recette_ingredient[Quantite]"]').value;
        data.push({Ingredient: ingredient, Quantite: quantity});
    });

    const uniqueData = [];
    const map = new Map();
    for (const item of data) {
        if (!map.has(item.Ingredient)) {
            map.set(item.Ingredient, true);
            uniqueData.push(item);
        }
    }
    document.getElementById('ingredients-data').value = JSON.stringify(uniqueData);
})

document.getElementById('add-ingredient').addEventListener('click', () => {
    addNewIngredientForm();
});

document.querySelectorAll('.remove-ingredient').forEach(button=>{
    addRemoveButtonListener(button);
})

function addNewIngredientForm(){
    const newForm = prototype.replace(/__name__/g, ingredientIndex);
    const newElement = document.createElement('div');
    newElement.classList.add('ingredient-form');
    newElement.innerHTML = newForm;

    const newFormElement = newElement.querySelector('.ingredient-form');
    newFormElement.querySelector('select[name="recette_ingredient[Ingredient]"]').value = '';
    newFormElement.querySelector('input[name="recette_ingredient[Quantite]"]').value= '';

    container.appendChild(newElement);

    newElement.querySelector('select[name="recette_ingredient[Ingredient]"]').addEventListener('change', event=>{
        const ingredientId = event.target.value;
        fetch(`getIngredientUnit/${ingredientId}`)
            .then(response=>response.json())
            .then(data=>{
                const quantiteInput = event.target.closest('.ingredient-form').querySelector('input[name="recette_ingredient[Quantite]"]');
                quantiteInput.placeholder = data.unit;
            })
    })
    addRemoveButtonListener(newElement.querySelector('.remove-ingredient'));
    ingredientIndex++;
}

function addRemoveButtonListener(button){
    button.addEventListener('click', (event)=>{
        event.target.closest('.ingredient-form').remove();
    })
}