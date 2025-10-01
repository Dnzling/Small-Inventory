<?php
session_start();
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inventory Management</title>
  <script src="../js/Vue.js"></script>
  <script src="../js/VueRouter.js"></script>
  <script src="../js/tailwindcss.js"></script>
  <script src="../resources/sweetalert2@11.js"></script>
</head>

<body class="bg-gray-50 text-gray-800">
  <div id="app">
    <!-- Navbar -->
    <nav class="bg-blue-600 text-white p-4 flex justify-between items-center shadow-md">
  <!-- App Title -->
  <router-link 
    to="/" 
    class="text-lg font-semibold tracking-wide hover:opacity-90 flex items-center gap-2"
  >
    📦 <span>Inventory Manager</span>
  </router-link>

  <!-- Right Section -->
  <div class="flex items-center gap-4">
    <!-- Username Badge -->
    <div class="flex items-center gap-2 bg-white text-blue-600 px-3 py-1 rounded-full shadow">
      <span class="font-medium">{{username}}</span>
    </div>

    <!-- Logout Button -->
    <button 
      @click="logout"
      class="bg-blue-500 hover:bg-blue-700 px-4 py-2 rounded-lg font-semibold transition-colors duration-200"
    >
      Logout
    </button>
  </div>
</nav>



    <!-- Main Content -->
    <main class="m-6">
      <router-view></router-view>
    </main>
  </div>

  <script>
    const {
      createApp,
      ref
    } = Vue;
    const {
      createRouter,
      createWebHashHistory,
      useRoute
    } = VueRouter;

    const API_URL = "../api/inventory.php";

    // Inventory List Component
    const InventoryList = {
      template: `<?php include 'viewInventory.html' ?>`,
      setup() {
        const items = ref([]);
        const itemData = ref({
          name: "",
          category: "",
          quantity: 0,
          price: 0
        });

        // Fetch Items
        const fetchItems = async () => {
          try {
            const response = await fetch(API_URL);
            items.value = await response.json();
          } catch (error) {
            console.error("Error loading items:", error);
          }
        };

        // Add Item
        const submitForm = async () => {
          try {
            // Send the form data with the 'create' action
            const response = await fetch(API_URL, {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
              },
              body: JSON.stringify({
                action: "create", // Specify that the action is 'create'
                name: itemData.value.name,
                category: itemData.value.category,
                quantity: itemData.value.quantity,
                price: itemData.value.price
              }),
            });

            const result = await response.json();

            if (result.success) {
              Swal.fire({
                icon: "success",
                title: "✅ Item added successfully!",
                showConfirmButton: false,
                timer: 1500
              });

              // Reset the form after successful addition
              itemData.value = {
                name: "",
                category: "",
                quantity: 0,
                price: 0
              };

              // Refresh the list of items
              fetchItems();

            } else {
              Swal.fire({
                icon: "error",
                title: "❌ Failed to add item",
                text: result.error || "Unknown error"
              });
            }
          } catch (error) {
            Swal.fire({
              icon: "error",
              title: "❌ Failed to add item!",
              text: error.message
            });
            console.error(error); // Log the error for debugging
          }

        };

        // Edit
        const editItem = (id) => {
          router.push(`/edit/${id}`);
        };

        const deleteItem = async (id) => {
          if (confirm("Are you sure you want to delete this item?")) {
            try {
              const response = await fetch(`${API_URL}`, {
                method: "POST", // Always POST
                headers: {
                  "Content-Type": "application/json",
                },
                body: JSON.stringify({
                  action: "delete", // Action to delete item
                  id: id
                })
              });

              const result = await response.json();
              if (result.success) {
                Swal.fire({
                  icon: "success",
                  title: "🗑️ Item deleted successfully!",
                  showConfirmButton: false,
                  timer: 1500
                });

                fetchItems(); // Refresh items list

              } else {
                Swal.fire({
                  icon: "error",
                  title: "❌ Failed to delete item",
                  text: result.error || "Unknown error"
                });
              }
            } catch (error) {
              Swal.fire({
                icon: "error",
                title: "❌ Error deleting item",
                text: error.message
              });
            }
          }
        };

        // CSV Export
        const downloadCSV = async () => {
          try {
            const response = await fetch(API_URL);
            const items = await response.json();

            // Header row
            const header = "Product Name,Category,Quantity,Price";

            // Data rows
            const rows = items.map(
              (item) => `${item.name},${item.category},${item.quantity},${item.price}`
            );

            // Combine header + rows
            const csvData = [header, ...rows].join("\n");

            // Create & download CSV file
            const blob = new Blob([csvData], {
              type: "text/csv"
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            // Get current date & time
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, "0");
            const dd = String(now.getDate()).padStart(2, "0");
            const hh = String(now.getHours()).padStart(2, "0");
            const min = String(now.getMinutes()).padStart(2, "0");

            // Format filename: inventory-YYYYMMDD-HHMM.csv
            const filename = `inventory-${yyyy}${mm}${dd}-${hh}${min}.csv`;

            link.download = filename;
            link.click();
          } catch (error) {
            console.error("Error generating CSV:", error);
            alert("Failed to download CSV.");
          }
        };


        // CSV Upload
        const uploadCSV = (event) => {
          const file = event.target.files[0];
          const reader = new FileReader();

          reader.onload = async () => {
            const rows = reader.result.trim().split("\n").map((row) => row.split(","));

            // Skip header row
            const dataRows = rows.slice(1);

            const items = dataRows.map((r) => ({
              name: r[0].trim(),
              category: r[1].trim(),
              quantity: parseInt(r[2], 10) || 0,
              price: parseFloat(r[3]) || 0,
            }));

            try {
              await fetch(API_URL, {
                method: "POST",
                headers: {
                  "Content-Type": "application/json"
                },
                body: JSON.stringify({
                  action: "bulk_create",
                  items
                }),
              });

              Swal.fire("📂 Success", "CSV uploaded successfully!", "success");
              fetchItems();
            } catch (error) {
              Swal.fire("❌ Error", "Failed to upload CSV", "error");
              console.error(error);
            }
          };
          reader.readAsText(file);
        };

        fetchItems();
        return {
          items,
          itemData,
          submitForm,
          editItem,
          deleteItem,
          downloadCSV,
          uploadCSV
        };
      },
    };

    // Edit Item Component
    const EditItem = {
      template: `
<div class="bg-white p-6 rounded-xl shadow-md max-w-xl mx-auto">
  <h1 class="text-xl font-semibold mb-4">Edit Inventory Item</h1>
  <form @submit.prevent="submitForm" class="space-y-4">
    <div class="relative">
      <input id="name" v-model="itemData.name" type="text" placeholder=" " required
        class="peer w-full border border-gray-300 rounded px-4 pt-5 pb-2 focus:outline-none focus:ring-2 focus:ring-blue-400" />
      <label for="name" class="absolute left-4 top-1.5 text-gray-500 text-xs transition-all 
           peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 
           peer-placeholder-shown:text-base peer-focus:top-1.5 
           peer-focus:text-xs peer-focus:text-blue-500">
        Name
      </label>
    </div>

    <!-- Category -->
    <div class="relative">
      <input id="category" v-model="itemData.category" type="text" placeholder=" "
        class="peer w-full border border-gray-300 rounded px-4 pt-5 pb-2 focus:outline-none focus:ring-2 focus:ring-blue-400" />
      <label for="category" class="absolute left-4 top-1.5 text-gray-500 text-xs transition-all 
           peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 
           peer-placeholder-shown:text-base peer-focus:top-1.5 
           peer-focus:text-xs peer-focus:text-blue-500">
        Category
      </label>
    </div>

    <!-- Quantity -->
    <div class="relative">
      <input id="quantity" v-model="itemData.quantity" type="number" min="1" step="1"
          onkeydown="return !['.','e','E','+','-'].includes(event.key)" placeholder=""
        class="peer w-full border border-gray-300 rounded px-4 pt-5 pb-2 focus:outline-none focus:ring-2 focus:ring-blue-400" />
      <label for="quantity" class="absolute left-4 top-1.5 text-gray-500 text-xs transition-all 
           peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 
           peer-placeholder-shown:text-base peer-focus:top-1.5 
           peer-focus:text-xs peer-focus:text-blue-500">
        Quantity
      </label>
    </div>

    <!-- Price -->
    <div class="relative">
      <input id="price" v-model="itemData.price" type="number" step="0.01" placeholder=" "
        class="peer w-full border border-gray-300 rounded px-4 pt-5 pb-2 focus:outline-none focus:ring-2 focus:ring-blue-400" />
      <label for="price" class="absolute left-4 top-1.5 text-gray-500 text-xs transition-all 
           peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 
           peer-placeholder-shown:text-base peer-focus:top-1.5 
           peer-focus:text-xs peer-focus:text-blue-500">
        Price
      </label>
    </div>

    <div class="flex gap-3 justify-end pt-3 border-t">
      <router-link to="/"><button type="button"
          class="px-5 py-2.5 rounded-xl bg-gray-200 font-medium hover:bg-gray-300">Cancel</button></router-link>
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-medium 
               hover:bg-blue-700 shadow-sm hover:shadow-lg transition">Update Item</button>
    </div>
  </form>
</div>
      `,
      setup() {
        const itemData = ref({
          id: null,
          name: "",
          category: "",
          quantity: 0,
          price: 0
        });

        const fetchItem = async (id) => {
          try {
            const response = await fetch(`${API_URL}?id=${id}`);
            itemData.value = await response.json();
          } catch (error) {
            console.error("Error loading item:", error);
          }
        };

        const submitForm = async () => {
          try {
            // Send a POST request with 'update' action instead of 'PUT'
            const response = await fetch(API_URL, {
              method: "POST", // Use POST instead of PUT
              headers: {
                "Content-Type": "application/json",
              },
              body: JSON.stringify({
                action: "update", // Specify the 'update' action
                id: itemData.value.id, // The item ID to update
                name: itemData.value.name,
                category: itemData.value.category,
                quantity: itemData.value.quantity,
                price: itemData.value.price
              }),
            });

            const result = await response.json();

            if (result.success) {
              Swal.fire({
                icon: "success",
                title: "✅ Item updated successfully!",
                showConfirmButton: false,
                timer: 1500
              }).then(() => {
                router.push("/"); // Navigate back to the main page or item list
              });

            } else {
              Swal.fire({
                icon: "error",
                title: "❌ Failed to update item",
                text: result.error || "Unknown error"
              });
            }
          } catch (error) {
            Swal.fire({
              icon: "error",
              title: "❌ Failed to update item!",
              text: error.message
            });
            console.error(error); // Log the error for debugging
          }
        };

        // Fetch item details when editing
        const route = useRoute();
        fetchItem(route.params.id);

        return {
          itemData,
          submitForm
        };
      }
    }

    // Vue Router
    const routes = [{
      path: "/",
      component: InventoryList
    },
    {
      path: "/add",
      component: InventoryList
    },
    {
      path: "/edit/:id",
      component: EditItem
    },
    ];

    const router = createRouter({
      history: createWebHashHistory(),
      routes,
    });

    // Create Vue App
    createApp({
      setup() {
        const username = ref('<?= $_SESSION["name"] ?? "Guest" ?>');
        const logout = () => {
          window.location.href = "../api/logout.php";
        };
        return {
          logout,
            username
        };
      }
    })
      .use(router)
      .mount("#app");
  </script>
</body>

</html>