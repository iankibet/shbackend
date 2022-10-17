import { defineUserConfig } from 'vuepress'
import { defaultTheme } from '@vuepress/theme-default'

export default defineUserConfig({
  lang: 'en-US',
  title: 'ShBackend',
  description: 'Backend Documentation',
  home: '/',
  sidebar: 'auto',
  themeConfig: {
    sidebar: 'auto',
    home: '/'
  },
  theme: defaultTheme({
    // default theme config
    // set config here
    colorMode: 'auto',
    home: '/',
    colorModeSwitch: true,
    navbar: [
      {
        text: 'Documentation',
        link: '/guide'
      },
      {
        text: 'Github',
        link: 'https://github.com/iankibet/shbackend'
      }
    ],
    sidebar: [
      // SidebarItem
      {
        text: 'Guide',
        link: '/guide/',
        children: [
          // SidebarItem
          {
            text: 'Requirements',
            link: '/guide/requirements.md',
          },
          {
            text: 'Installation',
            link: '/guide/installation.md',
          }
        ],
      },
      {
        text: 'commands',
        link: '/commands/',
        children: [
          {
            text: 'sh:init',
            link: '/commands/shinit.md',
          },
          {
            text: 'sh:make-model',
            link: '/commands/generatemodel.md',
          },
          {
            text: 'sh:make-endpoint',
            link: '/commands/makeapiendpoint.md',
          }
        ],
      },
      {
        text: 'Pagination',
        link: '/pagination/'
      },
      {
        text: 'ShRepisitory',
        link: '/shrepo/'
      },
      {
        text: 'Roles & Permissions',
        link: '/roles-permissions/'
      }
    ],
  }),
})
