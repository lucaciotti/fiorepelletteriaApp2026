// Configurazione ESLint (flat): esclude asset vendor compilati dalla lint.
// Serve a evitare linting su file generati/minificati (public/**).
export default [
    {
        ignores: [
            'public/**',
            'vendor/**',
            'node_modules/**',
            'storage/**',
            'bootstrap/cache/**',
        ],
    },
];
