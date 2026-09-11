---
applyTo: '**'
---

# Project Structure

The current folder organization is as follows:

```
PalletActivity/
├── .github/
│   └── instructions/
│       └── myinstructions.instructions.md
├── execute-implementation-plan.prompt.md
├── generate-implementation-plan.prompt.md
├── generate-stories-from-transcript.prompt.md
├── pallet_activity/
│   ├── .gitignore
│   ├── app/
│   │   ├── favicon.ico
│   │   ├── globals.css
│   │   ├── layout.tsx
│   │   └── page.tsx
│   ├── eslint.config.mjs
│   ├── next-env.d.ts
│   ├── next.config.ts
│   ├── package.json
│   ├── postcss.config.mjs
│   ├── public/
│   │   ├── file.svg
│   │   ├── globe.svg
│   │   ├── next.svg
│   │   ├── vercel.svg
│   │   └── window.svg
│   ├── README.md
│   └── tsconfig.json

# Next.js & TypeScript Best Practices

## Component Structure
- Always use functional components and React hooks.
- Organize components by feature or domain (feature-based folders), not by type (e.g., avoid global 'components' folder for all components).
- Co-locate component, styles, and tests in the same folder.
- Use PascalCase for component files and folders (e.g., UserProfile.tsx).
- Keep components small and focused; extract logic into custom hooks when reusable.
- Use TypeScript interfaces/types for all props and state.
- Prefer default exports for page components, named exports for shared components.
- Place shared UI components in a ui/ or common/ subfolder if needed.

## Performance
- Use React.memo for pure components to avoid unnecessary re-renders.
- Use Next.js dynamic imports (next/dynamic) for code splitting and lazy loading heavy components.
- Leverage getStaticProps/getServerSideProps for data fetching to optimize page load.
- Use the Image component from next/image for optimized images.
- Avoid anonymous functions and objects in JSX props to prevent re-renders.
- Use TypeScript's readonly and as const for immutable data where possible.
- Profile and monitor bundle size with Next.js built-in analyzer (next build && next export).
- Minimize third-party dependencies and prefer native/browser APIs when possible.

# Design Guidelines

- Use a modern UI style with minimalistic design, clean lines 
- Always redirect to the dashboard after login, and the dashboard should be the main entry point for users.
- Use smooth transitions and subtle animations to enhance interactivity.
- Ensure all layouts are fully responsive and mobile-friendly.
- Design components to look visually appealing on both desktop and mobile devices.
- Maintain accessibility (color contrast, keyboard navigation) even with neon/dark themes.
- Use consistent spacing, sizing, and alignment for a polished look.
- Always add full audited column for each tables (created date, created by, updated date, updated by, is deleted, deleted date, deleted by) and use soft delete for all tables.

# Libraries
- Use Tailwind CSS for styling and layout.
- Use next-intl or next-i18next for internationalization (i18n) support.
- Use prisma as the ORM for database interactions.
- Use docker for containerization and deployment.
- Use antdesign or material-ui for pre-built UI components if needed, but customize them to fit the design guidelines.
```