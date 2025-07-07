"use strict";

console.clear();

console.log("ciao");

const baseUrl = "http://localhost/boolean/06-php-test/api/videogames/index.php";

const fetchVideogames = async () => {
  const res = await axios.get(baseUrl);
  const container = document.getElementById("container");
  //   console.log(res.data);
  const videogames = res.data.slice(30, res.data.length);
  console.log(videogames);
  let cards = "";
  videogames.map((videogame) => {
    cards += `<div class="card" data-id=${videogame.id}>
      <h1>${videogame.name}</h1>
      <img src="${videogame.cover}" alt="${videogame.name}" />
      <p>${videogame.description}</p>
      <button data-id=${videogame.id} class="delete-btn" >Delete</button>
      </div>`;
  });
  container.innerHTML = cards;

  //   DELETE

  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.getAttribute("data-id");
      deleteVideogame(id);
    });
  });
};
fetchVideogames();

const deleteVideogame = async (id) => {
  try {
    await axios.delete(baseUrl + "?id=" + id);
    console.log("videogame deleted succesfully");
  } catch {
    throw new Error();
  }
};
