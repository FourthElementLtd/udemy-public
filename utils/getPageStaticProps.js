import { gql } from "@apollo/client";
import client from "client";
import {cleanAndTransformBlocks } from "utils/cleanAndTransformBlocks";
import { mapMainMenuItems } from "utils/mapMainMenuItems";

export const getPageStaticProps = async (context) => {
    const uri = context.params?.slug ? `/${context.params.slug.join("/")}/` : "/";
    const {data} = await client.query({
      query: gql`
      query PageQuery($uri: String!) {
        nodeByUri(uri: $uri) {
          ... on Page {
            id
            title
            blocks
            featuredImage {
              node {
                sourceUrl
              }
            }
            seo {
              title
              metaDesc
            }
          }

          ... on Property {
            id
            title
            blocks
            seo {
              title
              metaDesc
            }
          }

        }
  
        acfOptionsMainMenu {
          main_menu {
            callToActionButton {
              destination {
                ... on Page {
                  uri
                }
              }
              label
            }
  
            menuItems {
              menuItem {
                destination {
                  ... on Page {
                    uri
                  }
                }
                label
              }
              items {
                destination {
                  ... on Page {
                    uri
                  }
                }
                label
              }
            }
          }
        }
      }
      `,
      variables: {
        uri,
      },
    });
    const blocks = cleanAndTransformBlocks(data.nodeByUri.blocks);
    return {
      props: {
        seo: data.nodeByUri.seo,
        mainMenuItems: mapMainMenuItems(data.acfOptionsMainMenu.main_menu.menuItems),
        callToActionLabel: data.acfOptionsMainMenu.main_menu.callToActionButton.label,
        callToActionDestination:  data.acfOptionsMainMenu.main_menu.callToActionButton.destination.uri,
        blocks,
      },
    };

}