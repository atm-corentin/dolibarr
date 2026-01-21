<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    css/mycss.css.php
 * \ingroup mymodule
 * \brief   Example CSS.
 *
 * Put detailed description here.
 */

header('Content-Type: text/css');

?>

/*---- dropdown ----*/

.dropbtn {
    margin-left: 5px;
    margin-bottom: 5px;
    display: inline-block;
    cursor: pointer;
}

.dropdown, .dropdown-click {
    position: relative;
    display: inline-block;
    line-height: 1.5em;
}

.dropdown-click label {
    text-transform: capitalize;
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 100px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 9999;
    padding: 5px 0;
    -webkit-background-clip: padding-box;
    background-clip: padding-box;
    border: 1px solid #ccc;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 4px;
}

.dropdown-right {
    right: 0;
}

.dropdown-top {
    bottom: 100%;
}

.more-width {
    min-width: 140px;
}

.cursor-pointer {
    cursor: pointer;
}

.dropdown-image {
    max-width: 300px;
    max-height: 200px;
    display: inline-block;
    float: left;
}

.dropdown-content div {
    color: #555;
    font-size: 13px;
    text-decoration: none;
    display: block;
}

.dropdown-content a {
    color: #555;
    font-size: 13px;
    font-weight: bold;
    text-decoration: none;
    display: block;
    padding: 8px 12px;
}

.dropdown-content a:hover, .dropdown-content div:hover {background-color: #f1f1f1}

.dropdown:hover .dropdown-content {
    display: block;
}

.show {
    display: block;
}

.hidden {
    display: none;
    visibility: hidden;
}

.align-middle {
    vertical-align: middle;
}

/*---- end dropdown ----*/

<?php
