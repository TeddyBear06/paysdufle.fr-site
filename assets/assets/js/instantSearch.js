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
            attribute: "categories"
        }),
        instantsearch.widgets.refinementList({
            container: "#sous-categories-list",
            attribute: "sous-categories"
        }),
        instantsearch.widgets.refinementList({
            container: "#tags-list",
            attribute: "tags"
        }),
        instantsearch.widgets.configure({
            hitsPerPage: 6,
            snippetEllipsisText: "...",
            attributesToSnippet: ["contenu:50"]
        }),
        instantsearch.widgets.hits({
            container: "#lessons",
            templates: {
            item: `
                <a href="{{url}}">
                    <div>
                    <div class="lesson-titre">
                        {{#helpers.highlight}}{ "attribute": "titre" }{{/helpers.highlight}}
                    </div>
                    <img src="{{image}}" align="left" />
                    <div class="lesson-contenu">
                        {{#helpers.snippet}}{ "attribute": "contenu" }{{/helpers.snippet}}
                    </div>
                    <div class="lesson-info">Catégorie : {{categorie}}</div>
                    <div class="lesson-info">Sous-catégorie : {{sous-categorie}}</div>
                    <div class="lesson-info">Tags : {{tags}}</div>
                    </div>
                </a>
            `
            }
        }),
        instantsearch.widgets.pagination({
            container: "#pagination"
        })
    ]);

    search.start();

});