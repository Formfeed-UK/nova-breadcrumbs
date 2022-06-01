<template>
  <Card class="flex flex-col justify-center">
    <div class="breadcrumbs" v-if="shouldShowBreadcrumbs">
      <!-- Attach Related Models -->
      <div
        v-for="(item, key) in card.items"
        :key="key"
        class="breadcrumbs-item"
      >
        <span v-if="item.displayType === 'span'">
          <slot> {{ item.label }} </slot>
        </span>
        <a :href="linkHref(item)" v-else>
          {{ item.label }}
        </a>
        <HeroiconsSolidChevronRight class="chevron" v-if="key < card.items.length - 1" />
      </div>
    </div>
  </Card>
</template>

<script>
export default {
  props: [
    "card",

    // The following props are only available on resource detail cards...
    "resource",
    "resourceId",
    "resourceName",
  ],

  mounted() {},

  methods: {
    linkURI(item) {
      return `${item.resourceName ? "/resources/" : ""}${item.resourceName ?? ""}/${item.resourceId ?? ""}`;
    },

    linkData(item) {
      let search = new URLSearchParams();
      if (item.viaResource) search.set("viaResource", item.viaResource);
      if (item.viaResourceId) search.set("viaResourceId", item.viaResourceId);
      if (item.viaRelationship) search.set("viaRelationship", item.viaRelationship);
      return Array.from(search.entries()).length > 0 ? `?${search.toString()}` : "";
    },

    hashData(item) {
      let hash = new URLSearchParams();
      if (item.tab) hash.set(item.tabQuery ?? "tab", item.tab);
      return Array.from(hash.entries()).length > 0 ? `#${hash.toString()}` : "";
    },

    linkHref(item) {
      const link = item.url ?? `${this.linkURI(item)}${this.linkData(item)}${this.hashData(item )}`;
      return link;
    },
  },

  computed: {
    shouldShowBreadcrumbs() {
      return Object.keys(this.card.items).length > 0;
    },
  },
};
</script>

<style>
.breadcrumbs {
  padding: 12px;
  margin-top: 0;
  list-style: none;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
  border-radius: 10px;
}

.breadcrumbs .breadcrumbs-item {
  position: relative;
}

.breadcrumbs .breadcrumbs-item a {
  margin-right: 6px;
  margin-left: 8px;
  color: rgba(var(--colors-primary-500));
  text-decoration: none;
  font-weight: 600 !important;
}

.breadcrumbs .breadcrumbs-item span {
  display: inline-block;
  margin-right: 6px;
  margin-left: 8px;
  font-weight: 600 !important;
}

/*
.breadcrumbs .breadcrumbs-item:after {
  content: "\f054";
  font-family: "Font Awesome 5 Pro";
  line-height: 1;
  text-align: center;
  font-weight: 900;
  font-size: 0.79rem;
  vertical-align: -5%;
}
}*/

.breadcrumbs .breadcrumbs-item:last-child:after {
  display: none;
}
</style>
