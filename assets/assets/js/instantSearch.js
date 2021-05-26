$(document).ready(function () {

    const search = instantsearch({
    indexName: "lessons",
    searchClient: instantMeiliSearch(
        meilisearch_instance,
        meilisearch_api_key,
        {
            limitPerRequest: 30
        }
    )
    });

    search.addWidgets([
        instantsearch.widgets.searchBox({
            container: "#searchbox"
        }),
        instantsearch.widgets.clearRefinements({
            container: "#clear-refinements"
        }),
        instantsearch.widgets.refinementList({
            container: "#categories-list",
            attribute: "categorie"
        }),
        instantsearch.widgets.refinementList({
            container: "#sous-categories-list",
            attribute: "sous-categorie"
        }),
        instantsearch.widgets.refinementList({
            container: "#tags-list",
            attribute: "tags"
        }),
        instantsearch.widgets.configure({
            hitsPerPage: 8,
            snippetEllipsisText: "...",
            attributesToSnippet: ["contenu:50"]
        }),
        instantsearch.widgets.hits({
            container: "#hits",
            templates: {
            item: `
                <div class="card">
                    <img class="card-img-top" src="{{image}}" alt="Illustration leçon">
                    <div class="card-body">
                        <h5 class="card-title">{{#helpers.highlight}}{ "attribute": "titre" }{{/helpers.highlight}}</h5>
                        <p class="card-text">{{#helpers.snippet}}{ "attribute": "contenu" }{{/helpers.snippet}}</p>
                        <p class="card-text"><small class="text-muted">Catégorie : {{categorie}}</small></p>
                        <p class="card-text"><small class="text-muted">Sous-catégorie : {{sous-categorie}}</small></p>
                        <p class="card-text"><small class="text-muted">Tags : {{tags}}</small></p>
                    </div>
                    <div class="card-footer">
                        <a class="btn btn-link" href="{{url}}">Consulter la leçon</a>
                    </div>
                </div>
            `
            }
        }),
        instantsearch.widgets.pagination({
            container: "#pagination"
        })
    ]);

    search.start();

});