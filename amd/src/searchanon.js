// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Dynamic forms examples
 *
 * @module     local_altai/event_crud
 * @copyright  2023 Moodle
 * @author     David Castro <david.castro@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Templates from 'core/templates';

export const init = () => {
    var searchInput = document.getElementById('id_anonsearch');
    searchInput.setAttribute('hidden', 'hidden');
    let searchButton;
    let inputElement;
    let usercode;
    if (searchInput) {
        Templates.render('local_quizanon/searchbar', {searchInput: searchInput})
            .then((html) => {
                let newSearchInput = document.createElement('div');
                searchInput.parentElement.appendChild(newSearchInput);
                newSearchInput.innerHTML = html;
                searchButton = document.getElementById('searchbutton-anon');
                inputElement = document.getElementById('searchinput-anon');
                searchButton.addEventListener('click', function() {
                    usercode = inputElement.value;
                    let errorcontainer = document.getElementById('error-search-anon');
                    if (!usercode) {
                        errorcontainer.innerHTML = 'El campo no puede estar vacío';
                        errorcontainer.attributes.removeNamedItem('hidden');
                        return;
                    }
                    if (!/^[a-z0-9]{6}$/i.test(usercode)) {
                        errorcontainer.innerHTML = 'El código debe tener 6 caracteres y solo puede contener letras y números';
                        errorcontainer.attributes.removeNamedItem('hidden');
                        return;
                    }
                    let userElement = Array.from(document.querySelectorAll('h4')).find(el => el.textContent.includes(usercode));
                    if (userElement) {
                        errorcontainer.attributes.setNamedItem(document.createAttribute('hidden'));
                        userElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        userElement.style.backgroundColor = 'yellow';
                        setTimeout(() => {
                            userElement.style.backgroundColor = '';
                        }, 2000);
                    } else {
                        errorcontainer.innerHTML = 'No se ha encontrado ningún usuario con ese código';
                        errorcontainer.attributes.removeNamedItem('hidden');
                    }
                });
        });
    }
};